<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'resume' => array_merge(['required'], Setting::uploadRules()),
        ]);

        $remaining = $request->user()->remainingResumeQuota();
        if ($remaining !== null && $remaining <= 0) {
            return back()->with('error', 'Your plan\'s resume limit has been reached. Delete an existing resume or upgrade your plan.');
        }

        $file = $request->file('resume');

        Resume::create([
            'user_id' => $request->user()->id,
            'name' => $file->getClientOriginalName(),
            'file_path' => $file->store('documents'),
            'is_default' => $request->user()->resumes()->count() === 0,
        ]);

        return back()->with('status', 'Resume uploaded successfully.');
    }

    public function set_default(Request $request, Resume $resume)
    {
        if ($resume->user_id !== $request->user()->id) abort(403);

        $request->user()->resumes()->update(['is_default' => false]);
        $resume->update(['is_default' => true]);

        return back()->with('status', 'Default resume updated.');
    }

    public function destroy(Request $request, Resume $resume)
    {
        if ($resume->user_id !== $request->user()->id) abort(403);

        if (Storage::disk('local')->exists($resume->file_path)) {
            Storage::disk('local')->delete($resume->file_path);
        }
        $resume->delete();

        return back()->with('status', 'Resume deleted.');
    }
}
