<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeatureFlag;
use Inertia\Inertia;

class FeatureController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Features/Index', [
            'features' => FeatureFlag::where('key', 'like', 'feature.%')->orderBy('key')->get(),
        ]);
    }

    public function toggle(FeatureFlag $feature)
    {
        $feature->update(['enabled' => !$feature->enabled]);
        AuditLog::record($feature->enabled ? 'feature.enable' : 'feature.disable', $feature, ['key' => $feature->key]);

        return back()->with('status', "{$feature->label} " . ($feature->enabled ? 'enabled' : 'disabled') . '.');
    }
}
