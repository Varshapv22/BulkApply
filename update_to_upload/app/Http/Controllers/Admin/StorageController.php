<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StorageController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Storage/Index', [
            'usage' => [
                // Resumes and cover letters are stored in the same 'documents' folder — not split.
                // Resolved via the 'local' disk (not a hardcoded path) since Laravel 11+ moved its
                // default root to storage/app/private, which a hardcoded storage_path() would miss.
                'documents_kb' => $this->dirSizeKb(Storage::disk('local')->path('documents')),
                'logs_kb' => $this->dirSizeKb(storage_path('logs')),
                'cache_kb' => $this->dirSizeKb(storage_path('framework/cache')),
                'sessions_kb' => $this->dirSizeKb(storage_path('framework/sessions')),
            ],
            'diskFree_gb' => round(@disk_free_space(storage_path()) / 1024 / 1024 / 1024, 1),
            'diskTotal_gb' => round(@disk_total_space(storage_path()) / 1024 / 1024 / 1024, 1),
        ]);
    }

    public function cleanCache()
    {
        Artisan::call('cache:clear');

        return back()->with('status', 'Application cache cleared.');
    }

    private function dirSizeKb(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return (int) round($size / 1024);
    }
}
