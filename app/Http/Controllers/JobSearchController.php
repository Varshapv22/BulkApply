<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Services\JobSearchService;
use Illuminate\Http\Request;

class JobSearchController extends Controller
{
    public function index()
    {
        $profile = Profile::current();

        return view('search', [
            'profile' => $profile,
            'results' => [],
            'searched' => false,
            'error' => null,
        ]);
    }

    /**
     * Search for jobs using the API.
     */
    public function search(Request $request, JobSearchService $service)
    {
        $data = $request->validate([
            'role'     => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'sites'    => ['nullable', 'array'],
            'sites.*'  => ['string', 'in:' . implode(',', array_keys(Profile::JOB_SITES))],
        ]);

        $profile = Profile::current();

        // Save preferences for next time
        $profile->fill([
            'preferred_role'  => $data['role'],
            'location'        => $data['location'] ?? $profile->location,
            'preferred_sites' => $data['sites'] ?? [],
        ]);
        if ($profile->exists) {
            $profile->save();
        }

        $result = $service->search(
            $data['role'],
            $data['location'] ?? '',
            $data['sites'] ?? [],
            30
        );

        return view('search', [
            'profile'  => $profile,
            'results'  => $result['jobs'],
            'searched' => true,
            'error'    => $result['error'],
        ]);
    }

    /**
     * Import selected search results into job_applications and auto-apply.
     */
    public function autoApply(Request $request)
    {
        $request->validate([
            'jobs'   => ['required', 'array', 'min:1'],
            'jobs.*' => ['required', 'array'],
        ]);

        $profile = Profile::current();

        if (!$profile->hasDocuments()) {
            return back()->with('error', 'Upload your resume and cover letter on the Profile page before applying.');
        }

        $imported = 0;
        $emailApplied = 0;
        $linkOnly = 0;

        foreach ($request->input('jobs') as $jobData) {
            $company = $jobData['company'] ?? null;
            $email   = $jobData['recruiter_email'] ?? null;

            if (!$company) continue;

            // Skip duplicates
            $exists = JobApplication::where('company', $company)
                ->where('job_title', $jobData['job_title'] ?? '')
                ->exists();
            if ($exists) continue;

            $applyType = ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) ? 'email' : 'link';

            $job = JobApplication::create([
                'company'         => $company,
                'job_title'       => $jobData['job_title'] ?? null,
                'recruiter_name'  => null,
                'recruiter_email' => $email ?: ($jobData['company'] . '@noreply.example.com'),
                'job_url'         => $jobData['job_url'] ?? null,
                'location'        => $jobData['location'] ?? null,
                'notes'           => 'Auto-found via ' . ($jobData['source'] ?? 'job search'),
                'source'          => $jobData['source'] ?? null,
                'apply_type'      => $applyType,
                'apply_url'       => $jobData['apply_url'] ?? $jobData['job_url'] ?? null,
                'status'          => $applyType === 'email'
                    ? JobApplication::STATUS_QUEUED
                    : JobApplication::STATUS_PENDING,
            ]);

            $imported++;

            // Auto-send email applications immediately
            if ($applyType === 'email') {
                SendJobApplication::dispatch($job->id);
                $emailApplied++;
            } else {
                $linkOnly++;
            }
        }

        $msg = "Added {$imported} job(s).";
        if ($emailApplied > 0) {
            $msg .= " {$emailApplied} application(s) queued for email delivery.";
        }
        if ($linkOnly > 0) {
            $msg .= " {$linkOnly} job(s) require manual application via their portal (see Apply links).";
        }

        return redirect()->route('jobs.index')->with('status', $msg);
    }
}
