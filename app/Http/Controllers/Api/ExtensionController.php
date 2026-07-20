<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
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
        ]);

        $user = $request->user();

        // Check for duplicates
        $exists = JobApplication::where('company', $data['company'])
            ->where('job_title', $data['job_title'] ?? '')
            ->where('user_id', $user->id)
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
            'status' => JobApplication::STATUS_PENDING,
            'user_id' => $user->id,
            'resume_id' => $user->resumes()->where('is_default', true)->value('id'),
            'apply_type' => 'link',
            'apply_url' => $data['job_url'] ?? null,
            'recruiter_email' => str_replace(' ', '', strtolower($data['company'])) . '@noreply.example.com',
        ]);

        return response()->json([
            'message' => 'Job saved successfully!',
            'job' => $job,
        ], 201);
    }
}
