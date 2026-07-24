<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userCounts = [
            'total'        => User::count(),
            'active'       => User::where('is_active', true)->count(),
            'new_today'    => User::whereDate('created_at', today())->count(),
        ];

        $applicationCounts = [
            'total'   => JobApplication::count(),
            'pending' => JobApplication::where('status', JobApplication::STATUS_PENDING)->count(),
            'queued'  => JobApplication::where('status', JobApplication::STATUS_QUEUED)->count(),
            'sent'    => JobApplication::where('status', JobApplication::STATUS_SENT)->count(),
            'failed'  => JobApplication::where('status', JobApplication::STATUS_FAILED)->count(),
        ];

        $emailSuccessRate = $applicationCounts['total'] > 0
            ? round(($applicationCounts['sent'] / $applicationCounts['total']) * 100)
            : 0;

        $queueCounts = [
            'active' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'userCounts'        => $userCounts,
            'applicationCounts' => $applicationCounts,
            'emailSuccessRate'  => $emailSuccessRate,
            'totalResumes'      => Resume::count(),
            'queueCounts'       => $queueCounts,
        ]);
    }
}
