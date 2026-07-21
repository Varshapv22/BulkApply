<?php

namespace App\Http\Controllers;

use App\Jobs\SendJobApplication;
use App\Models\EmailTemplate;
use App\Models\JobApplication;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
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

        $jobs = $query->get()->map(fn ($job) => [
            'id'              => $job->id,
            'company'         => $job->company,
            'job_title'       => $job->job_title,
            'job_url'         => $job->job_url,
            'apply_type'      => $job->apply_type,
            'apply_url'       => $job->apply_url,
            'source'          => $job->source,
            'recruiter_name'  => $job->recruiter_name,
            'recruiter_email' => $job->recruiter_email,
            'status'          => $job->status,
            'error'           => $job->error,
            'error_short'     => $job->error ? Str::limit($job->error, 40) : null,
            'pipeline_status' => $job->pipeline_status,
            'opened_at'       => $job->opened_at?->toDateTimeString(),
            'clicked_at'      => $job->clicked_at?->toDateTimeString(),
            'followup_count'  => $job->followup_count,
            'sent_at'         => $job->sent_at ? $job->sent_at->diffForHumans() : null,
        ]);

        $profile = Profile::current();
        $activeBatch = null;
        if ($profile->current_send_batch_id) {
            $batch = Bus::findBatch($profile->current_send_batch_id);
            if ($batch && ! $batch->finished()) {
                $activeBatch = [
                    'total'     => $batch->totalJobs,
                    'processed' => $batch->processedJobs(),
                    // $batch->failedJobs only counts jobs whose exception escaped
                    // to the queue; SendJobApplication catches send errors itself
                    // and marks the row failed, so count that directly instead.
                    'failed'    => JobApplication::where('send_batch_id', $batch->id)
                        ->where('status', JobApplication::STATUS_FAILED)->count(),
                    'pending'   => $batch->pendingJobs,
                    'cancelled' => $batch->cancelled(),
                ];
            } elseif ($profile->exists) {
                $profile->update(['current_send_batch_id' => null]);
            }
        }

        return Inertia::render('Jobs', [
            'jobs'            => $jobs,
            'hasDocuments'    => $profile->hasDocuments(),
            'templates'      => EmailTemplate::all(['id', 'name', 'is_default']),
            'pipelineLabels'  => JobApplication::PIPELINE_STATUSES,
            'activeBatch'     => $activeBatch,
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
        ]);

        JobApplication::create($data + [
            'status'  => JobApplication::STATUS_PENDING,
            'user_id' => Auth::id(),
            'resume_id' => Auth::user()->resumes()->where('is_default', true)->value('id'),
        ]);

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
                'recruiter_name'  => $record['  '] ?? null,
                'recruiter_email' => $email,
                'job_url'         => $record['job_url'] ?? null,
                'location'        => $record['location'] ?? null,
                'notes'           => $record['notes'] ?? null,
                'status'          => JobApplication::STATUS_PENDING,
                'user_id'         => Auth::id(),
                'resume_id'       => Auth::user()->resumes()->where('is_default', true)->value('id'),
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
        if (! $profile->hasMailCredentials()) {
            return back()->with('error', 'Connect your email sender in Settings before sending.');
        }

        $jobs = JobApplication::sendable()->get();

        if ($jobs->isEmpty()) {
            return back()->with('error', 'No pending jobs to send.');
        }

        $templateId = $request->input('email_template_id');

        $batch = Bus::batch(
            $jobs->pluck('id')->map(fn ($id) => new SendJobApplication($id))->all()
        )->name("Bulk send ({$profile->user_id})")->allowFailures()->dispatch();

        $updateData = [
            'status'        => JobApplication::STATUS_QUEUED,
            'error'         => null,
            'send_batch_id' => $batch->id,
        ];
        if ($templateId) {
            $updateData['email_template_id'] = $templateId;
        }
        JobApplication::whereIn('id', $jobs->pluck('id'))->update($updateData);

        $profile->update(['current_send_batch_id' => $batch->id]);

        return redirect()->route('jobs.index')
            ->with('status', "Queued {$jobs->count()} application(s). They are being emailed in the background.");
    }

    /**
     * Cancel the profile's currently in-flight bulk send batch. Jobs already
     * picked up by a worker finish normally; anything still queued is
     * skipped (SkipIfBatchCancelled) and its row reset to pending.
     */
    public function cancelSend()
    {
        $profile = Profile::current();

        if ($profile->current_send_batch_id) {
            Bus::findBatch($profile->current_send_batch_id)?->cancel();

            JobApplication::where('send_batch_id', $profile->current_send_batch_id)
                ->where('status', JobApplication::STATUS_QUEUED)
                ->update(['status' => JobApplication::STATUS_PENDING]);

            $profile->update(['current_send_batch_id' => null]);
        }

        return back()->with('status', 'Remaining sends cancelled.');
    }

    public function sendOne(JobApplication $job)
    {
        $profile = Profile::current();

        if (! $profile->hasDocuments()) {
            return back()->with('error', 'Upload your resume and cover letter on the Profile page before sending.');
        }
        if (! $profile->hasMailCredentials()) {
            return back()->with('error', 'Connect your email sender in Settings before sending.');
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
     * Export all jobs to CSV.
     */
    public function export(): StreamedResponse
    {
        $columns = [
            'company', 'job_title', 'recruiter_name', 'recruiter_email',
            'job_url', 'location', 'notes', 'status', 'pipeline_status',
            'sent_at', 'opened_at', 'clicked_at', 'followup_count', 'created_at',
        ];

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            JobApplication::orderBy('created_at', 'desc')
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
        $columns = ['company', 'job_title', 'recruiter_name', 'recruiter_email', 'job_url', 'location', 'notes'];
        $sample  = ['Acme Corp', 'Backend Engineer', 'Jane Doe', 'jane@acme.com', 'https://acme.com/jobs/123', 'Remote', 'Referred by a friend'];

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
            default => null,
        };
    }
}
