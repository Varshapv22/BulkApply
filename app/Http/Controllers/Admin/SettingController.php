<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request, Setting $setting)
    {
        $rules = match ($setting->type) {
            'boolean' => ['value' => ['nullable', 'boolean']],
            'integer' => ['value' => ['nullable', 'integer', 'min:0']],
            default => ['value' => ['nullable', 'string', 'max:2000']],
        };

        $data = $request->validate($rules);

        $value = $setting->type === 'boolean' ? (int) $request->boolean('value') : $data['value'];
        $setting->update(['value' => $value]);
        AuditLog::record('setting.update', $setting, ['key' => $setting->key, 'value' => $value]);

        return back()->with('status', "{$setting->label} updated.");
    }
}
