<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendJobApplication;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query()->with('user:id,name,email')
            ->search($request->input('search'))
            ->filterStatus($request->input('status'));

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowed = ['company', 'job_title', 'status', 'created_at', 'sent_at'];
        if (!in_array($sortField, $allowed)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';
        $query->orderBy($sortField, $sortDir);

        $jobs = $query->paginate(25)->withQueryString()->through(fn ($job) => [
            'id' => $job->id,
            'company' => $job->company,
            'job_title' => $job->job_title,
            'status' => $job->status,
            'error_short' => $job->error ? Str::limit($job->error, 60) : null,
            'user' => $job->user ? ['id' => $job->user->id, 'name' => $job->user->name, 'email' => $job->user->email] : null,
            'sent_at' => $job->sent_at?->toDateTimeString(),
            'created_at' => $job->created_at,
        ]);

        return Inertia::render('Admin/Applications/Index', [
            'jobs' => $jobs,
            'filters' => $request->only('search', 'status', 'sort', 'dir'),
            'stats' => [
                'byCompany' => JobApplication::select('company', DB::raw('COUNT(*) as count'))
                    ->groupBy('company')->orderByDesc('count')->limit(5)->get(),
                'byUser' => JobApplication::select('user_id', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('user_id')->groupBy('user_id')->orderByDesc('count')->limit(5)
                    ->with('user:id,name')->get()->map(fn ($r) => ['name' => $r->user?->name ?? 'Unknown', 'count' => $r->count]),
                'successRate' => JobApplication::count() > 0
                    ? round(JobApplication::where('status', JobApplication::STATUS_SENT)->count() / JobApplication::count() * 100)
                    : 0,
            ],
        ]);
    }

    public function retry(JobApplication $job)
    {
        $job->update(['status' => JobApplication::STATUS_QUEUED, 'error' => null]);
        SendJobApplication::dispatch($job->id);

        return back()->with('status', "Application to {$job->company} re-queued.");
    }

    public function destroy(JobApplication $job)
    {
        $job->delete();

        return back()->with('status', 'Application deleted.');
    }

    public function export(): StreamedResponse
    {
        $columns = ['id', 'user_id', 'company', 'job_title', 'status', 'created_at', 'sent_at'];

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            JobApplication::orderBy('created_at', 'desc')->chunk(200, function ($jobs) use ($out, $columns) {
                foreach ($jobs as $job) {
                    fputcsv($out, array_map(fn ($c) => $job->$c, $columns));
                }
            });

            fclose($out);
        }, 'admin-applications.csv', ['Content-Type' => 'text/csv']);
    }
}
