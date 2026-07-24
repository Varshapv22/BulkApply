<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResumeController extends Controller
{
    public function index()
    {
        $resumes = Resume::with('user:id,name,email')->latest()->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'is_default' => $r->is_default,
            'size_kb' => Storage::exists($r->file_path) ? round(Storage::size($r->file_path) / 1024) : null,
            'user' => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email] : null,
            'created_at' => $r->created_at,
        ]);

        return Inertia::render('Admin/Resumes/Index', [
            'resumes' => $resumes,
            'totalStorageKb' => $resumes->sum('size_kb'),
        ]);
    }

    public function download(Resume $resume)
    {
        if (!Storage::exists($resume->file_path)) {
            return back()->with('error', 'File no longer exists on disk.');
        }

        return Storage::download($resume->file_path, $resume->name);
    }

    public function destroy(Resume $resume)
    {
        Storage::delete($resume->file_path);
        $resume->delete();

        return back()->with('status', 'Resume deleted.');
    }
}
