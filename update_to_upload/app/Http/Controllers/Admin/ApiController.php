<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use App\Models\ApiRequestLog;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Api/Index', [
            'configs' => ApiConfig::orderBy('key')->get()->map(fn ($c) => [
                'id' => $c->id,
                'key' => $c->key,
                'label' => $c->label,
                'description' => $c->description,
                'has_value' => filled($c->value),
                'active' => $c->active,
            ]),
            'stats' => [
                'total' => ApiRequestLog::count(),
                'failed' => ApiRequestLog::where('status', '>=', 400)->count(),
                'avgDurationMs' => (int) ApiRequestLog::avg('duration_ms'),
            ],
            'recentRequests' => ApiRequestLog::latest('created_at')->limit(50)->get(),
        ]);
    }

    public function updateConfig(Request $request, ApiConfig $config)
    {
        $data = $request->validate([
            'value' => ['nullable', 'string', 'max:2000'],
            'active' => ['boolean'],
        ]);

        $config->update($data);
        // Never record the actual secret value in the audit trail — just that it changed.
        AuditLog::record('api_config.update', $config, ['key' => $config->key, 'value_changed' => array_key_exists('value', $data)]);

        return back()->with('status', "{$config->label} updated.");
    }
}
