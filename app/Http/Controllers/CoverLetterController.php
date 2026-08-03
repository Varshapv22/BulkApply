<?php

namespace App\Http\Controllers;

use App\Models\CoverLetter;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CoverLetterController extends Controller
{
    public function store(Request $request)
    {
        $mode = $request->input('mode');

        $data = $request->validate([
            'mode' => ['required', 'in:file,text'],
            'name' => ['required', 'string', 'max:255'],
            'cover_letter' => array_merge([$mode === 'file' ? 'required' : 'nullable'], Setting::uploadRules()),
            'text' => [$mode === 'text' ? 'required' : 'nullable', 'string', 'max:10000'],
        ]);

        $remaining = $request->user()->remainingCoverLetterQuota();
        if ($remaining !== null && $remaining <= 0) {
            return back()->with('error', 'Your plan\'s cover letter limit has been reached. Delete an existing cover letter or upgrade your plan.');
        }

        $attributes = [
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'mode' => $data['mode'],
            'is_default' => $request->user()->coverLetters()->count() === 0,
        ];

        if ($data['mode'] === 'file') {
            $attributes['file_path'] = $request->file('cover_letter')->store('documents');
        } else {
            $attributes['text'] = $data['text'] ?? '';
        }

        CoverLetter::create($attributes);

        return back()->with('status', 'Cover letter added successfully.');
    }

    public function set_default(Request $request, CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== $request->user()->id) abort(403);

        $request->user()->coverLetters()->update(['is_default' => false]);
        $coverLetter->update(['is_default' => true]);

        return back()->with('status', 'Default cover letter updated.');
    }

    public function destroy(Request $request, CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== $request->user()->id) abort(403);

        if ($coverLetter->file_path && Storage::exists($coverLetter->file_path)) {
            Storage::delete($coverLetter->file_path);
        }
        $coverLetter->delete();

        return back()->with('status', 'Cover letter deleted.');
    }
}
