<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    private function folder(): string
    {
        return config('backup.backup.name');
    }

    public function index()
    {
        $disk = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
        $folder = $this->folder();

        $files = collect($disk->exists($folder) ? $disk->files($folder) : [])
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->map(fn ($f) => [
                'name' => basename($f),
                'path' => $f,
                'size_kb' => round($disk->size($f) / 1024),
                'created_at' => date('Y-m-d H:i:s', $disk->lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('Admin/Backup/Index', [
            'backups' => $files,
        ]);
    }

    public function run()
    {
        Artisan::call('backup:run', ['--only-db' => true]);
        AuditLog::record('backup.run');

        return back()->with('status', 'Backup started/completed: ' . trim(Artisan::output()));
    }

    public function download(string $name)
    {
        $disk = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
        $path = $this->folder() . '/' . basename($name);

        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path);
    }

    public function destroy(string $name)
    {
        $disk = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
        $path = $this->folder() . '/' . basename($name);

        $disk->delete($path);
        AuditLog::record('backup.delete', null, ['file' => basename($name)]);

        return back()->with('status', 'Backup deleted.');
    }
}
