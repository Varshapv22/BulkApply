<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // The busiest page in the app, run through ~10 aggregate queries on
        // every load — cache the computed payload briefly per user rather
        // than re-running all of them on every visit/refresh.
        $data = Cache::remember("dashboard:{$userId}", 60, fn () => $this->buildData($userId));

        return Inertia::render('Dashboard', $data);
    }

    private function buildData(int $userId): array
    {
        $statusCounts = JobApplication::where('user_id', $userId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'total'   => $statusCounts->sum(),
            'pending' => $statusCounts[JobApplication::STATUS_PENDING] ?? 0,
            'queued'  => $statusCounts[JobApplication::STATUS_QUEUED] ?? 0,
            'sent'    => $statusCounts[JobApplication::STATUS_SENT] ?? 0,
            'failed'  => $statusCounts[JobApplication::STATUS_FAILED] ?? 0,
        ];

        $sentRate = $counts['total'] > 0
            ? round(($counts['sent'] / $counts['total']) * 100)
            : 0;

        // Daily applications over the last 30 days (based on created_at)
        $dailyActivity = JobApplication::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent")
            )
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing days so the chart has no gaps
        $chartData = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData->push([
                'date'  => $date,
                'label' => now()->subDays($i)->format('M j'),
                'total' => (int) ($dailyActivity[$date]->total ?? 0),
                'sent'  => (int) ($dailyActivity[$date]->sent ?? 0),
            ]);
        }

        // This week vs last week
        $thisWeek = JobApplication::where('user_id', $userId)->where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = JobApplication::where('user_id', $userId)->whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->startOfWeek(),
        ])->count();

        // Top 5 companies
        $topCompanies = JobApplication::select('company', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('company')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent activity (last 10 sent or failed)
        $recentActivity = JobApplication::where('user_id', $userId)
            ->whereIn('status', [
                JobApplication::STATUS_SENT,
                JobApplication::STATUS_FAILED,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($job) => [
                'id'              => $job->id,
                'company'         => $job->company,
                'job_title'       => $job->job_title,
                'recruiter_name'  => $job->recruiter_name,
                'recruiter_email' => $job->recruiter_email,
                'status'          => $job->status,
                'when'            => $job->updated_at->diffForHumans(),
            ]);

        // Email tracking stats
        $tracking = [
            'opened'  => JobApplication::where('user_id', $userId)->whereNotNull('opened_at')->count(),
            'clicked' => JobApplication::where('user_id', $userId)->whereNotNull('clicked_at')->count(),
        ];
        $tracking['open_rate'] = $counts['sent'] > 0
            ? round(($tracking['opened'] / $counts['sent']) * 100)
            : 0;

        // Pipeline breakdown
        $pipelineStats = JobApplication::select('pipeline_status', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('pipeline_status')
            ->pluck('count', 'pipeline_status')
            ->all();

        // Company replies live on their own page (sidebar → Replies).
        return [
            'counts'         => $counts,
            'sentRate'       => $sentRate,
            'chartData'      => $chartData->values(),
            'thisWeek'       => $thisWeek,
            'lastWeek'       => $lastWeek,
            'weekStart'      => now()->startOfWeek()->format('M j'),
            'topCompanies'   => $topCompanies,
            'recentActivity' => $recentActivity,
            'tracking'       => $tracking,
            'pipelineStats'  => $pipelineStats,
            'pipelineLabels' => JobApplication::PIPELINE_STATUSES,
        ];
    }
}
