<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    public static function groups(): array
    {
        return [
            'clinic' => [
                'clinic.name' => ['label' => 'Tên phòng khám', 'type' => 'text'],
                'clinic.address' => ['label' => 'Địa chỉ',        'type' => 'text'],
                'clinic.phone' => ['label' => 'Điện thoại',     'type' => 'text'],
                'clinic.tax_code' => ['label' => 'Mã số thuế',    'type' => 'text'],
                'clinic.email' => ['label' => 'Email',          'type' => 'text'],
            ],
            'schedule' => [
                'schedule.default_duration' => ['label' => 'Thời lượng mặc định (phút)', 'type' => 'number'],
                'schedule.working_hours' => ['label' => 'Giờ làm việc (VD: 08:00-17:00)', 'type' => 'text'],
            ],
        ];
    }
}
