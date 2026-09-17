<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'variance_warning_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'alert_email' => ['nullable', 'email', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                SystemSetting::set($key, $value, 'general');
            }
        }

        // Currency default threshold values that are computed at runtime.
        cache()->forget('system_settings');

        AuditLog::record([
            'action' => 'update',
            'module' => 'settings',
            'description' => 'System settings updated',
            'new_values' => $data,
        ]);

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}