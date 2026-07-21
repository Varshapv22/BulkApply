<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Reports/Index', [
            'registrations' => User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get(),
            'byCompany' => JobApplication::select('company', DB::raw('COUNT(*) as count'))
                ->groupBy('company')->orderByDesc('count')->limit(10)->get(),
            'byPipeline' => JobApplication::select('pipeline_status', DB::raw('COUNT(*) as count'))
                ->groupBy('pipeline_status')->pluck('count', 'pipeline_status'),
            'emailStats' => [
                'sent' => JobApplication::where('status', JobApplication::STATUS_SENT)->count(),
                'failed' => JobApplication::where('status', JobApplication::STATUS_FAILED)->count(),
                'opened' => JobApplication::whereNotNull('opened_at')->count(),
                'clicked' => JobApplication::whereNotNull('clicked_at')->count(),
                'total' => JobApplication::count(),
            ],
            'resumesByDay' => Resume::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get(),
            'autoApply' => [
                'imported' => JobApplication::where('notes', 'like', 'Auto-found via%')->count(),
                'emailApplied' => JobApplication::where('notes', 'like', 'Auto-found via%')->where('apply_type', 'email')->count(),
                'linkOnly' => JobApplication::where('notes', 'like', 'Auto-found via%')->where('apply_type', 'link')->count(),
            ],
        ]);
    }

    public function export(string $type): StreamedResponse
    {
        $reports = [
            'registrations' => fn () => [['Date', 'Count'], User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get()->map(fn ($r) => [$r->date, $r->count])],
            'companies' => fn () => [['Company', 'Applications'], JobApplication::select('company', DB::raw('COUNT(*) as count'))->groupBy('company')->orderByDesc('count')->get()->map(fn ($r) => [$r->company, $r->count])],
            'pipeline' => fn () => [['Pipeline Status', 'Count'], JobApplication::select('pipeline_status', DB::raw('COUNT(*) as count'))->groupBy('pipeline_status')->get()->map(fn ($r) => [$r->pipeline_status, $r->count])],
        ];

        abort_unless(isset($reports[$type]), 404);

        [$header, $rows] = $reports[$type]();

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) fputcsv($out, $row);
            fclose($out);
        }, "report-{$type}.csv", ['Content-Type' => 'text/csv']);
    }
}
