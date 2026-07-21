<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        return Inertia::render('Admin/Analytics/Index', [
            'days' => $days,
            'userGrowth' => $this->dailySeries(User::class, $days),
            'applicationGrowth' => $this->dailySeries(JobApplication::class, $days),
            'resumeGrowth' => $this->dailySeries(Resume::class, $days),
            'emailPerformance' => [
                'sent' => JobApplication::where('status', JobApplication::STATUS_SENT)->where('sent_at', '>=', now()->subDays($days))->count(),
                'failed' => JobApplication::where('status', JobApplication::STATUS_FAILED)->where('updated_at', '>=', now()->subDays($days))->count(),
                'opened' => JobApplication::whereNotNull('opened_at')->where('opened_at', '>=', now()->subDays($days))->count(),
                'clicked' => JobApplication::whereNotNull('clicked_at')->where('clicked_at', '>=', now()->subDays($days))->count(),
            ],
        ]);
    }

    private function dailySeries(string $model, int $days): array
    {
        $rows = $model::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $series[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'count' => (int) ($rows[$date]->count ?? 0),
            ];
        }

        return $series;
    }
}
