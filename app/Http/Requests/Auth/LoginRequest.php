<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Đổi thứ người dùng gõ vào thành đúng email đang lưu trong CSDL.
     *
     * Chấp nhận cả ba kiểu: email đầy đủ, chỉ tên tài khoản (phần trước @),
     * và gõ hoa thường tuỳ ý. Cần bước này vì Postgres phân biệt hoa thường,
     * nên Auth::attempt() so khớp email bằng dấu bằng sẽ trượt nếu chỉ khác cỡ chữ.
     */
    protected function resolveEmail(): string
    {
        $login = trim((string) $this->input('login'));

        if ($login === '') {
            return $login;
        }

        // Vô hiệu hoá ký tự đại diện của LIKE để "a%b" không khớp bừa.
        // Dùng "!" làm ký tự thoát cho gọn, tránh rắc rối trích dẫn dấu gạch chéo ngược.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $login);

        // Có "@" thì coi là email đầy đủ; không có thì ghép thêm phần đuôi bất kỳ.
        $pattern = str_contains($login, '@') ? $escaped : $escaped.'@%';

        // LOWER() ở cả hai vế nên chạy đúng trên cả Postgres lẫn SQLite (dùng khi chạy test).
        return User::query()
            ->whereRaw("LOWER(email) LIKE LOWER(?) ESCAPE '!'", [$pattern])
            ->orderBy('id')
            ->value('email') ?? $login;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => $this->resolveEmail(),
            'password' => (string) $this->input('password'),
        ];

        // luôn ghi nhớ đăng nhập: đăng nhập một lần dùng lâu dài, không cần đăng nhập lại
        if (! Auth::attempt($credentials, true)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
