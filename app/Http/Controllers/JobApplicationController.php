<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    public function index()
    {
        return view('jobs', [
            'jobs'    => JobApplication::latest()->get(),
            'profile' => Profile::current(),
            'counts'  => [
                'total'   => JobApplication::count(),
                'pending' => JobApplication::whereIn('status', [JobApplication::STATUS_PENDING, JobApplication::STATUS_FAILED])->count(),
                'sent'    => JobApplication::where('status', JobApplication::STATUS_SENT)->count(),
                'failed'  => JobApplication::where('status', JobApplication::STATUS_FAILED)->count(),
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
        ]);

        JobApplication::create($data + ['status' => JobApplication::STATUS_PENDING]);

        return redirect()->route('jobs.index')->with('status', 'Job added.');
    }

    /**
     * Import jobs from a CSV. Headers are matched case-insensitively and can use
     * spaces or underscores (e.g. "Recruiter Email" == recruiter_email).
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

        // Normalize headers: "Recruiter Email" -> recruiter_email
        $map = [];
        foreach ($header as $i => $col) {
            $key = str_replace(' ', '_', strtolower(trim((string) $col)));
            $map[$i] = $this->aliasColumn($key);
        }

        $imported = 0;
        $skipped  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $record = [];
            foreach ($row as $i => $value) {
                if (isset($map[$i]) && $map[$i] !== null) {
                    $record[$map[$i]] = trim((string) $value);
                }
            }

            $email = $record['recruiter_email'] ?? null;
            $company = $record['company'] ?? null;

            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $company) {
                $skipped++;
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
                'status'          => JobApplication::STATUS_PENDING,
            ]);
            $imported++;
        }

        fclose($handle);

        return redirect()->route('jobs.index')
            ->with('status', "Imported {$imported} job(s)." . ($skipped ? " Skipped {$skipped} row(s) missing a company or valid recruiter email." : ''));
    }

    /**
     * Queue a send for every pending/failed job. Guards against sending with no
     * resume + cover letter uploaded.
     */
    public function send()
    {
        $profile = Profile::current();

        if (! $profile->hasDocuments()) {
            return back()->with('error', 'Upload your resume and cover letter on the Profile page before sending.');
        }

        $jobs = JobApplication::sendable()->get();

        if ($jobs->isEmpty()) {
            return back()->with('error', 'No pending jobs to send.');
        }

        foreach ($jobs as $job) {
            $job->update(['status' => JobApplication::STATUS_QUEUED, 'error' => null]);
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
     * Download a ready-to-fill CSV template.
     */
    public function template(): StreamedResponse
    {
        $columns = ['company', 'job_title', 'recruiter_name', 'recruiter_email', 'job_url', 'location', 'notes'];
        $sample  = ['Acme Corp', 'Backend Engineer', 'Jane Doe', 'jane@acme.com', 'https://acme.com/jobs/123', 'Remote', 'Referred by a friend'];

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            fputcsv($out, $sample);
            fclose($out);
        }, 'jobs-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Accept a few friendly aliases for column headers.
     */
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
            default => null,
        };
    }
}
