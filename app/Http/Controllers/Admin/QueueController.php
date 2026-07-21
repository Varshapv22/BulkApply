<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QueueController extends Controller
{
    public function index()
    {
        $batches = DB::table('job_batches')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'total_jobs' => $b->total_jobs,
                'pending_jobs' => $b->pending_jobs,
                'failed_jobs' => $b->failed_jobs,
                'cancelled' => !is_null($b->cancelled_at),
                'finished' => !is_null($b->finished_at),
                'created_at' => date('Y-m-d H:i:s', $b->created_at),
            ]);

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(30)
            ->get(['id', 'connection', 'queue', 'exception', 'failed_at']);

        return Inertia::render('Admin/Queue/Index', [
            'batches' => $batches,
            'failedJobs' => $failedJobs->map(fn ($j) => [
                'id' => $j->id,
                'queue' => $j->queue,
                'exception_short' => \Illuminate\Support\Str::limit(explode("\n", $j->exception)[0], 120),
                'failed_at' => $j->failed_at,
            ]),
            'pendingCount' => DB::table('jobs')->count(),
        ]);
    }

    public function cancelBatch(string $batch)
    {
        Bus::findBatch($batch)?->cancel();

        return back()->with('status', 'Batch cancelled.');
    }

    public function deleteFailedJob(int $id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();

        return back()->with('status', 'Failed job entry removed.');
    }
}
