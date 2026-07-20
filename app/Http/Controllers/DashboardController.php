<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = [
            'total'   => JobApplication::count(),
            'pending' => JobApplication::where('status', JobApplication::STATUS_PENDING)->count(),
            'queued'  => JobApplication::where('status', JobApplication::STATUS_QUEUED)->count(),
            'sent'    => JobApplication::where('status', JobApplication::STATUS_SENT)->count(),
            'failed'  => JobApplication::where('status', JobApplication::STATUS_FAILED)->count(),
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
        $thisWeek = JobApplication::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = JobApplication::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->startOfWeek(),
        ])->count();

        // Top 5 companies
        $topCompanies = JobApplication::select('company', DB::raw('COUNT(*) as count'))
            ->groupBy('company')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent activity (last 10 sent or failed)
        $recentActivity = JobApplication::whereIn('status', [
                JobApplication::STATUS_SENT,
                JobApplication::STATUS_FAILED,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        // Email tracking stats
        $tracking = [
            'opened'  => JobApplication::whereNotNull('opened_at')->count(),
            'clicked' => JobApplication::whereNotNull('clicked_at')->count(),
        ];
        $tracking['open_rate'] = $counts['sent'] > 0
            ? round(($tracking['opened'] / $counts['sent']) * 100)
            : 0;

        // Pipeline breakdown
        $pipelineStats = JobApplication::select('pipeline_status', DB::raw('COUNT(*) as count'))
            ->groupBy('pipeline_status')
            ->pluck('count', 'pipeline_status')
            ->all();

        return view('dashboard', compact(
            'counts', 'sentRate', 'chartData', 'thisWeek', 'lastWeek',
            'topCompanies', 'recentActivity', 'tracking', 'pipelineStats'
        ));
    }
}
