<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('settings.view');

        $groups = Setting::groups();
        $current = Setting::pluck('value', 'key')->toArray();

        $settings = [];
        foreach ($groups as $group => $keys) {
            $settings[$group] = array_map(fn ($meta) => $meta, $keys);
            foreach ($keys as $key => $meta) {
                $settings[$group][$key]['value'] = $current[$key] ?? '';
            }
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:1000',
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Đã lưu cấu hình.');
    }
}
