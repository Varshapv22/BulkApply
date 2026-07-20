<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\EmailTemplate;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query()
            ->search($request->input('search'))
            ->filterStatus($request->input('status'))
            ->filterPipeline($request->input('pipeline'));

        $sortField = $request->input('sort', 'created_at');
        $sortDir   = $request->input('dir', 'desc');
        $allowed   = ['company', 'job_title', 'status', 'pipeline_status', 'created_at', 'sent_at'];
        if (!in_array($sortField, $allowed)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        // Always show active jobs (pending/queued/failed) first, then sent
        $query->orderByRaw("CASE WHEN status IN ('pending','queued','failed') THEN 0 ELSE 1 END ASC")
              ->orderBy($sortField, $sortDir);

        return view('jobs', [
            'jobs'      => $query->get(),
            'profile'   => Profile::current(),
            'templates' => EmailTemplate::all(),
            'counts'    => [
                'total'   => JobApplication::count(),
                'pending' => JobApplication::whereIn('status', [JobApplication::STATUS_PENDING, JobApplication::STATUS_FAILED])->count(),
                'sent'    => JobApplication::where('status', JobApplication::STATUS_SENT)->count(),
                'failed'  => JobApplication::where('status', JobApplication::STATUS_FAILED)->count(),
            ],
            'filters' => [
                'search'   => $request->input('search'),
                'status'   => $request->input('status'),
                'pipeline' => $request->input('pipeline'),
                'sort'     => $sortField,
                'dir'      => $sortDir,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company'        => ['required', 'string', 'max:255'],
            'job_title'      => ['nullable', 'string', 'max:255'],
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'recruiter_email'=> ['required', 'email', 'max:255'],
            'job_url'        => ['nullable', 'url', 'max:2048'],
            'location'       => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
            'company_website'=> ['nullable', 'url', 'max:2048'],
            'requirements'   => ['nullable', 'string'],
        ]);

        JobApplication::create($data + ['status' => JobApplication::STATUS_PENDING]);

        return redirect()->route('jobs.index')->with('status', 'Job added.');
    }

    /**
     * Import jobs from a CSV with duplicate detection.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return back()->with('error', 'The CSV appears to be empty.');
        }

        $map = [];
        foreach ($header as $i => $col) {
            $key = str_replace(' ', '_', strtolower(trim((string) $col)));
            $map[$i] = $this->aliasColumn($key);
        }

        $imported   = 0;
        $skipped    = 0;
        $duplicates = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $record = [];
            foreach ($row as $i => $value) {
                if (isset($map[$i]) && $map[$i] !== null) {
                    $record[$map[$i]] = trim((string) $value);
                }
            }

            $email   = $record['recruiter_email'] ?? null;
            $company = $record['company'] ?? null;

            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $company) {
                $skipped++;
                continue;
            }

            // Duplicate detection: same email + same company
            $isDuplicate = JobApplication::where('recruiter_email', $email)
                ->where('company', $company)
                ->exists();

            if ($isDuplicate) {
                $duplicates++;
                continue;
            }

            JobApplication::create([
                'company'         => $company,
                'job_title'       => $record['job_title'] ?? null,
                'recruiter_name'  => $record['recruiter_name'] ?? null,
                'recruiter_email' => $email,
                'job_url'         => $record['job_url'] ?? null,
                'location'        => $record['location'] ?? null,
                'notes'           => $record['notes'] ?? null,
                'company_website' => $record['company_website'] ?? null,
                'requirements'    => $record['requirements'] ?? null,
                'status'          => JobApplication::STATUS_PENDING,
            ]);
            $imported++;
        }

        fclose($handle);

        $msg = "Imported {$imported} job(s).";
        if ($skipped) $msg .= " Skipped {$skipped} row(s) missing a company or valid recruiter email.";
        if ($duplicates) $msg .= " Skipped {$duplicates} duplicate(s) (same email + company already exists).";

        return redirect()->route('jobs.index')->with('status', $msg);
    }

    /**
     * Queue sends, optionally using a specific email template.
     */
    public function send(Request $request)
    {
        $profile = Profile::current();

        if (! $profile->hasDocuments()) {
            return back()->with('error', 'Upload your resume and cover letter on the Profile page before sending.');
        }

        $jobs = JobApplication::sendable()->get();

        if ($jobs->isEmpty()) {
            return back()->with('error', 'No pending jobs to send.');
        }

        $templateId = $request->input('email_template_id');

        foreach ($jobs as $job) {
            $updateData = ['status' => JobApplication::STATUS_QUEUED, 'error' => null];
            if ($templateId) {
                $updateData['email_template_id'] = $templateId;
            }
            $job->update($updateData);
            SendJobApplication::dispatch($job->id);
        }

        return redirect()->route('jobs.index')
            ->with('status', "Queued {$jobs->count()} application(s). They are being emailed in the background.");
    }

    public function sendOne(JobApplication $job)
    {
        $profile = Profile::current();

        if (! $profile->hasDocuments()) {
            return back()->with('error', 'Upload your resume and cover letter on the Profile page before sending.');
        }

        $job->update(['status' => JobApplication::STATUS_QUEUED, 'error' => null]);
        SendJobApplication::dispatch($job->id);

        return back()->with('status', "Application to {$job->company} queued.");
    }

    /**
     * Update the pipeline status of a job application.
     */
    public function updatePipeline(Request $request, JobApplication $job)
    {
        $data = $request->validate([
            'pipeline_status' => ['required', 'in:' . implode(',', array_keys(JobApplication::PIPELINE_STATUSES))],
        ]);

        $job->update($data);

        return back()->with('status', "Status for {$job->company} updated to {$data['pipeline_status']}.");
    }

    /**
     * Preview an email with placeholders filled in.
     */
    public function preview(Request $request)
    {
        $profile = Profile::current();

        $job = JobApplication::find($request->input('job_id'));
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $templateId = $request->input('email_template_id');
        if ($templateId) {
            $template = EmailTemplate::find($templateId);
            $subject = $template ? $template->subject : $profile->email_subject;
            $body = $template ? $template->body : $profile->email_body;
        } else {
            $subject = $profile->email_subject ?: 'Application for {job_title} at {company}';
            $body = $profile->email_body ?: '';
        }

        return response()->json([
            'subject' => $job->renderTemplate($subject, $profile),
            'body'    => nl2br(e($job->renderTemplate($body, $profile))),
            'to'      => $job->recruiter_email,
        ]);
    }

    public function destroy(JobApplication $job)
    {
        $job->delete();

        return back()->with('status', 'Job removed.');
    }

    public function clear()
    {
        JobApplication::query()->delete();

        return back()->with('status', 'All jobs cleared.');
    }

    /**
     * Export jobs to CSV (respects current filters).
     */
    public function export(Request $request): StreamedResponse
    {
        $columns = [
            'company', 'job_title', 'recruiter_name', 'recruiter_email',
            'job_url', 'location', 'company_website', 'requirements', 'notes', 'status', 'pipeline_status',
            'sent_at', 'opened_at', 'clicked_at', 'followup_count', 'created_at',
        ];

        return response()->streamDownload(function () use ($columns, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            JobApplication::query()
                ->search($request->input('search'))
                ->filterStatus($request->input('status'))
                ->filterPipeline($request->input('pipeline'))
                ->orderBy('created_at', 'desc')
                ->chunk(200, function ($jobs) use ($out, $columns) {
                    foreach ($jobs as $job) {
                        $row = [];
                        foreach ($columns as $col) {
                            $val = $job->{$col};
                            $row[] = $val instanceof \Carbon\Carbon ? $val->toDateTimeString() : $val;
                        }
                        fputcsv($out, $row);
                    }
                });

            fclose($out);
        }, 'applications-export-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Download a ready-to-fill CSV template.
     */
    public function template(): StreamedResponse
    {
        $columns = ['company', 'job_title', 'recruiter_name', 'recruiter_email', 'job_url', 'location', 'company_website', 'requirements', 'notes'];
        $sample  = ['Acme Corp', 'Backend Engineer', 'Jane Doe', 'jane@acme.com', 'https://acme.com/jobs/123', 'Remote', 'https://acme.com', 'PHP, Laravel, 3+ years exp', 'Referred by a friend'];

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            fputcsv($out, $sample);
            fclose($out);
        }, 'jobs-template.csv', ['Content-Type' => 'text/csv']);
    }

    private function aliasColumn(string $key): ?string
    {
        return match ($key) {
            'company', 'company_name', 'organization', 'employer' => 'company',
            'job_title', 'title', 'role', 'position'             => 'job_title',
            'recruiter_name', 'recruiter', 'contact', 'contact_name', 'name' => 'recruiter_name',
            'recruiter_email', 'email', 'recruiter_mail', 'mail', 'contact_email' => 'recruiter_email',
            'job_url', 'url', 'link', 'job_link'                 => 'job_url',
            'location', 'city', 'place'                          => 'location',
            'notes', 'note', 'comments', 'remark'               => 'notes',
            'company_website', 'website', 'site', 'url'         => 'company_website',
            'requirements', 'reqs', 'qualifications', 'skills'  => 'requirements',
            default => null,
        };
    }

    /**
     * Search for jobs online using RapidAPI JSearch.
     */
    public function searchOnline(Request $request)
    {
        $role = $request->input('role');
        $location = $request->input('location');

        if (!$role || !$location) {
            return response()->json(['error' => 'Role and location are required.'], 400);
        }

        $apiKey = env('RAPIDAPI_KEY');
        $apiHost = env('RAPIDAPI_HOST', 'jsearch.p.rapidapi.com');

        if (!$apiKey) {
            return response()->json(['error' => 'RAPIDAPI_KEY is missing in your .env file.'], 500);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-rapidapi-key' => $apiKey,
                'x-rapidapi-host' => $apiHost,
            ])->get("https://{$apiHost}/search", [
                'query' => "{$role} in {$location}",
                'page' => '1',
                'num_pages' => '1',
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Failed to fetch jobs from RapidAPI. Please check your API key.'], 500);
            }

            $data = $response->json();
            $jobs = $data['data'] ?? [];

            $formattedResults = [];
            foreach ($jobs as $job) {
                $formattedResults[] = [
                    'company' => $job['employer_name'] ?? 'Unknown Company',
                    'job_title' => $job['job_title'] ?? $role,
                    'recruiter_name' => '', // APIs rarely provide recruiter names directly
                    'recruiter_email' => '', // APIs rarely provide recruiter emails directly
                    'location' => $job['job_city'] ? ($job['job_city'] . ', ' . ($job['job_state'] ?? '')) : $location,
                    'job_url' => $job['job_apply_link'] ?? '',
                    'company_website' => $job['employer_website'] ?? '',
                    'requirements' => \Illuminate\Support\Str::limit($job['job_description'] ?? '', 150),
                ];
            }

            return response()->json(['results' => $formattedResults]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching jobs: ' . $e->getMessage()], 500);
        }
    }
}
