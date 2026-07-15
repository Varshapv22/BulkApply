<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private const DEFAULT_BODY = <<<TXT
Dear {recruiter_name},

I am writing to apply for the {job_title} position at {company}. I have attached my resume and cover letter for your review.

I would welcome the opportunity to discuss how my experience fits this role. Thank you for your time and consideration.

Best regards,
{your_name}
TXT;

    public function edit()
    {
        return view('profile', [
            'profile'     => Profile::current(),
            'defaultBody' => self::DEFAULT_BODY,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name'     => ['nullable', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'email_subject' => ['required', 'string', 'max:255'],
            'email_body'    => ['required', 'string'],
            'resume'        => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cover_letter'  => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $profile = Profile::current();

        $profile->fill([
            'full_name'     => $data['full_name'] ?? null,
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'email_subject' => $data['email_subject'],
            'email_body'    => $data['email_body'],
        ]);

        if ($request->hasFile('resume')) {
            $this->deleteIfExists($profile->resume_path);
            $file = $request->file('resume');
            $profile->resume_path = $file->store('documents');
            $profile->resume_name = $file->getClientOriginalName();
        }

        if ($request->hasFile('cover_letter')) {
            $this->deleteIfExists($profile->cover_letter_path);
            $file = $request->file('cover_letter');
            $profile->cover_letter_path = $file->store('documents');
            $profile->cover_letter_name = $file->getClientOriginalName();
        }

        $profile->save();

        return redirect()->route('profile.edit')->with('status', 'Profile saved.');
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
