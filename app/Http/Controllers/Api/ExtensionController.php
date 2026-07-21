<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ExtensionController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('chrome-extension')->plainTextToken,
        ]);
    }

    public function storeJob(Request $request)
    {
        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'job_url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'apply_type' => ['nullable', 'string', 'in:email,link,easy_apply'],
        ]);

        $user = $request->user();

        // Duplicate check: same company+title, or the exact same posting URL
        // scraped again (title/casing can drift slightly between visits).
        $exists = JobApplication::where('user_id', $user->id)
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('company', $data['company'])
                        ->where('job_title', $data['job_title'] ?? '');
                });
                if (! empty($data['job_url'])) {
                    $query->orWhere('job_url', $data['job_url']);
                }
            })
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Job already exists in your dashboard.'], 409);
        }

        $job = JobApplication::create([
            'company' => $data['company'],
            'job_title' => $data['job_title'] ?? null,
            'job_url' => $data['job_url'] ?? null,
            'location' => $data['location'] ?? null,
            'source' => $data['source'] ?? 'Extension',
            'notes' => $data['description'] ?? null,
            'recruiter_name' => $data['recruiter_name'] ?? null,
            'status' => JobApplication::STATUS_PENDING,
            'user_id' => $user->id,
            'resume_id' => $user->resumes()->where('is_default', true)->value('id'),
            'apply_type' => $data['apply_type'] ?? 'link',
            'apply_url' => $data['job_url'] ?? null,
            'recruiter_email' => str_replace(' ', '', strtolower($data['company'])) . '@noreply.example.com',
        ]);

        return response()->json([
            'message' => 'Job saved successfully!',
            'job' => $job,
        ], 201);
    }

    /**
     * Profile fields the Easy Apply assist can use to fill known form
     * fields (name/email/phone) and pick an existing resume by name.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();
        $defaultResumeName = $user->resumes()->where('is_default', true)->value('name');

        return response()->json([
            'fullName' => $profile?->full_name ?: $user->name,
            'email' => $profile?->email ?: $user->email,
            'phone' => $profile?->phone,
            'resumeName' => $defaultResumeName,
        ]);
    }

    /**
     * Records the outcome of the Easy Apply auto-fill assist against the job
     * it was run for. The assist never submits on the user's behalf, so
     * status here reflects "filled" (ready for manual review/submit) or
     * "failed" (couldn't find the button/modal), not a real submission.
     */
    public function autoApplyStatus(Request $request, JobApplication $job)
    {
        if ($job->user_id !== $request->user()->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:filled,submitted,failed'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        $job->update([
            'auto_apply_status' => $data['status'],
            'auto_apply_error' => $data['error'] ?? null,
            'applied_at' => in_array($data['status'], ['filled', 'submitted'], true) ? now() : null,
        ]);

        return response()->json(['message' => 'Status recorded.']);
    }
}
