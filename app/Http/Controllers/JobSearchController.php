<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Services\JobSearchService;
use App\Services\SiteJobService;
use App\Services\SkillExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class JobSearchController extends Controller
{
    public function index()
    {
        $profile = Profile::current();

        return Inertia::render('Search', [
            'profile'      => $this->profileProps($profile),
            'jobSites'     => Profile::JOB_SITES,
            'results'      => [],
            'searched'     => false,
            'searchError'  => null,
            'hasDocuments' => $profile->hasDocuments(),
            'resumes'      => \Illuminate\Support\Facades\Auth::user()->resumes()->orderByDesc('is_default')->get(),
        ]);
    }

    /**
     * Search for jobs using the API.
     */
    public function search(Request $request, JobSearchService $service)
    {
        $data = $request->validate([
            'role'      => ['required_without:site', 'nullable', 'string', 'max:255'],
            'location'  => ['nullable', 'string', 'max:255'],
            'site'      => ['nullable', 'string', 'max:2048'],
            'sort_by'       => ['nullable', 'in:relevance,date,salary'],
            'full_time'     => ['nullable', 'boolean'],
            'find_contacts' => ['nullable', 'boolean'],
        ]);

        $role = trim($data['role'] ?? '');
        $site = trim($data['site'] ?? '');

        $profile = Profile::current();

        // Save preferences for next time
        $profile->fill([
            'preferred_role' => $role ?: $profile->preferred_role,
            'location'       => $data['location'] ?? $profile->location,
        ]);
        if ($profile->exists) {
            $profile->save();
        }

        $adzunaOpts = [
            'sort_by'       => $data['sort_by'] ?? 'relevance',
            'full_time'     => $request->boolean('full_time'),
            'find_contacts' => $request->boolean('find_contacts'),
        ];

        if ($site !== '') {
            // The candidate named a specific site/company.
            $siteResult = (new SiteJobService())->search($role, $site, 30);

            if ($siteResult['handled'] && !empty($siteResult['jobs'])) {
                // We read the site directly.
                $result = ['jobs' => $siteResult['jobs'], 'error' => $siteResult['error']];
            } elseif ($siteResult['handled']) {
                // Recognised a site/URL but got nothing usable — fall back to the
                // aggregated index (using the site's name as a keyword) with a note.
                $agg  = $service->search($role, $data['location'] ?? '', $adzunaOpts + ['keyword' => $this->siteKeyword($site)], 30);
                $note = $siteResult['error'] ?: 'No listings could be read from that site.';
                $result = !empty($agg['jobs'])
                    ? ['jobs' => $agg['jobs'], 'error' => $note . ' Showing related jobs from across the web instead.']
                    : ['jobs' => [], 'error' => $siteResult['error'] ?? ($agg['error'] ?? 'No jobs found.')];
            } elseif (!empty($siteResult['platform'])) {
                // A big platform (Indeed/Naukri/LinkedIn…) we can't scrape, but the
                // aggregator already indexes jobs from it — search role + location.
                $agg = $service->search($role, $data['location'] ?? '', $adzunaOpts, 30);
                $note = $siteResult['platform'] . " can't be read directly (it blocks automated access), so these are matching jobs aggregated from across the web — many originate from " . $siteResult['platform'] . '.';
                $result = !empty($agg['jobs'])
                    ? ['jobs' => $agg['jobs'], 'error' => $note]
                    : ['jobs' => [], 'error' => $agg['error'] ?? 'No jobs found.'];
            } else {
                // A bare name we can't fetch — aggregated search with it as keyword.
                $result = $service->search($role, $data['location'] ?? '', $adzunaOpts + ['keyword' => $site], 30);
            }
        } else {
            // Plain aggregated search across the whole web.
            $result = $service->search($role, $data['location'] ?? '', $adzunaOpts, 30);
        }

        $jobs = $this->attachSkills($result['jobs'], $profile->skills ?? '');

        return Inertia::render('Search', [
            'profile'      => $this->profileProps($profile),
            'jobSites'     => Profile::JOB_SITES,
            'results'      => $jobs,
            'searched'     => true,
            'searchError'  => $result['error'],
            'hasDocuments' => $profile->hasDocuments(),
            'resumes'      => \Illuminate\Support\Facades\Auth::user()->resumes()->orderByDesc('is_default')->get(),
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

    /**
     * Derive a search keyword from a site input (URL or name).
     * e.g. "https://infopark.in/jobs" -> "infopark".
     */
    private function siteKeyword(string $site): string
    {
        $isUrlish = Str::startsWith($site, ['http://', 'https://'])
            || (!str_contains($site, ' ') && preg_match('/\.[a-z]{2,}(\/|$)/i', $site));

        if ($isUrlish) {
            $url  = Str::startsWith($site, ['http://', 'https://']) ? $site : 'https://' . $site;
            $host = parse_url($url, PHP_URL_HOST) ?: $site;
            $host = preg_replace('/^www\./', '', $host);
            return explode('.', $host)[0] ?: $host;
        }

        return $site;
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
