<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ExtensionController extends Controller
{
    public function index()
    {
        $manifestPath = base_path('extension/manifest.json');
        $zipPath = public_path('downloads/bulkapply-extension.zip');

        return Inertia::render('Admin/Extension/Index', [
            'version' => File::exists($manifestPath) ? (json_decode(File::get($manifestPath), true)['version'] ?? null) : null,
            'zipUpdatedAt' => File::exists($zipPath) ? date('Y-m-d H:i:s', File::lastModified($zipPath)) : null,
            'sourceCounts' => JobApplication::whereNotNull('source')
                ->select('source', DB::raw('COUNT(*) as count'))
                ->groupBy('source')->orderByDesc('count')->get(),
            'applyTypeCounts' => JobApplication::select('apply_type', DB::raw('COUNT(*) as count'))
                ->whereNotNull('apply_type')->groupBy('apply_type')->pluck('count', 'apply_type'),
            'easyApplyStatusCounts' => JobApplication::where('apply_type', 'easy_apply')
                ->select('auto_apply_status', DB::raw('COUNT(*) as count'))
                ->groupBy('auto_apply_status')->pluck('count', 'auto_apply_status'),
            'recentTokenUsage' => DB::table('personal_access_tokens')
                ->where('personal_access_tokens.name', 'chrome-extension')
                ->whereNotNull('personal_access_tokens.last_used_at')
                ->orderByDesc('personal_access_tokens.last_used_at')
                ->limit(10)
                ->join('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
                ->get(['users.name', 'users.email', 'personal_access_tokens.last_used_at']),
        ]);
    }
}
