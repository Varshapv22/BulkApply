<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\ApiConfig;
use App\Models\FeatureFlag;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Services\JobQueryService;
use App\Services\LocationDirectory;
use App\Services\SkillExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JobSearchController extends Controller
{
    public function index()
    {
        if (!FeatureFlag::enabled('feature.job_search')) {
            abort(403, 'Job Search is currently disabled by the administrator.');
        }

        $profile = Profile::current();

        return Inertia::render('Search', [
            'profile'      => $this->profileProps($profile),
            'jobSites'     => Profile::JOB_SITES,
            'locationSuggestions' => LocationDirectory::suggestions($this->internationalSearchAvailable()),
            'results'      => [],
            'searched'     => false,
            'searchError'  => null,
            'hasDocuments' => $profile->hasDocuments(),
            'resumes'      => Auth::user()->resumes()->orderByDesc('is_default')->get(),
            'savedSearches' => Auth::user()->savedSearches()->latest()->get(),
        ]);
    }

    /**
     * Search for jobs using the API.
     */
    public function search(Request $request, JobQueryService $service)
    {
        $data = $request->validate([
            'role'      => ['required_without:site', 'nullable', 'string', 'max:255'],
            'location'  => ['nullable', 'string', 'max:255'],
            'company'   => ['nullable', 'string', 'max:255'],
            'site'      => ['nullable', 'string', 'max:2048'],
            'sort_by'       => ['nullable', 'in:relevance,date,salary'],
            'full_time'     => ['nullable', 'boolean'],
            'find_contacts' => ['nullable', 'boolean'],
        ]);

        $role    = trim($data['role'] ?? '');
        $company = trim($data['company'] ?? '');
        $site    = trim($data['site'] ?? '');

        $profile = Profile::current();

        // Save preferences for next time
        $profile->fill([
            'preferred_role' => $role ?: $profile->preferred_role,
            'location'       => $data['location'] ?? $profile->location,
        ]);
        if ($profile->exists) {
            $profile->save();
        }

        $result = $service->run($role, $data['location'] ?? '', $site, [
            'sort_by'       => $data['sort_by'] ?? 'relevance',
            'full_time'     => $request->boolean('full_time'),
            'find_contacts' => $request->boolean('find_contacts'),
        ], 30, $company);

        $resultJobs = $result['jobs'];

        // When a site is set (park / URL search), still apply the company name
        // as a post-filter so the user can narrow park results to one employer.
        if ($company !== '' && $site !== '') {
            $needle = mb_strtolower($company);
            $resultJobs = array_values(array_filter($resultJobs, function ($job) use ($needle) {
                return str_contains(mb_strtolower($job['company'] ?? ''), $needle);
            }));
        }

        $jobs = $this->attachSkills($resultJobs, $profile->skills ?? '');

        return Inertia::render('Search', [
            'profile'      => $this->profileProps($profile),
            'jobSites'     => Profile::JOB_SITES,
            'locationSuggestions' => LocationDirectory::suggestions($this->internationalSearchAvailable()),
            'results'      => $jobs,
            'searched'     => true,
            'searchError'  => $result['error'],
            'hasDocuments' => $profile->hasDocuments(),
            'resumes'      => Auth::user()->resumes()->orderByDesc('is_default')->get(),
            'savedSearches' => Auth::user()->savedSearches()->latest()->get(),
        ]);
    }

    /**
     * Detect skills mentioned in each job (from title + description) and mark
     * which ones overlap with the candidate's own skill list, regardless of
     * which source the job came from (Adzuna, a tech park, or a scraped site).
     */
    private function attachSkills(array $jobs, string $candidateSkillsRaw): array
    {
        $extractor = new SkillExtractor();
        $candidateSkills = $extractor->parseCandidateSkills($candidateSkillsRaw);

        foreach ($jobs as &$job) {
            $text = ($job['job_title'] ?? '') . ' ' . ($job['description'] ?? '');
            $jobSkills = $extractor->extract($text);
            $match = $extractor->matchAgainst($jobSkills, $candidateSkills);

            $job['skills_matched'] = $match['matched'];
            $job['skills_other']   = $match['other'];
        }

        return $jobs;
    }

    /** Whether JSearch (the UAE/rest-of-Asia fallback) is enabled and configured. */
    private function internationalSearchAvailable(): bool
    {
        return FeatureFlag::enabled('source.jsearch')
            && filled(ApiConfig::get('jsearch_api_key', config('services.jsearch.api_key')));
    }

    /**
     * Minimal profile shape needed by the search page.
     */
    private function profileProps(Profile $profile): array
    {
        return [
            'preferred_role'  => $profile->preferred_role,
            'location'        => $profile->location,
            'preferred_sites' => $profile->preferred_sites ?? [],
            'skills'          => $profile->skills,
            'has_skills'      => filled($profile->skills),
        ];
    }

    /**
     * Import selected search results into job_applications and auto-apply.
     */
    public function autoApply(Request $request)
    {
        $request->validate([
            'jobs'   => ['required', 'array', 'min:1'],
            'jobs.*' => ['required', 'array'],
            'resume_id' => ['nullable', 'exists:resumes,id'],
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

            // Skip duplicates (for this user only)
            $exists = JobApplication::where('user_id', $profile->user_id)
                ->where('company', $company)
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
                'user_id'         => $profile->user_id,
                'resume_id'       => $request->input('resume_id') ?: $profile->user->resumes()->where('is_default', true)->value('id'),
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
