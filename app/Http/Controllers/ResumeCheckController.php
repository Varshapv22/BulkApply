<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\ResumeAtsAnalyzer;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResumeCheckController extends Controller
{
    /**
     * Analyze the uploaded resume for ATS friendliness.
     */
    public function index(ResumeAtsAnalyzer $analyzer)
    {
        $profile = Profile::current();

        $report = null;
        $error = null;

        if ($profile->resume_path && Storage::exists($profile->resume_path)) {
            try {
                $report = $analyzer->analyze(
                    Storage::path($profile->resume_path),
                    $profile->resume_name ?: basename($profile->resume_path),
                    $profile->preferred_role ?? ''
                );
            } catch (\Throwable $e) {
                $error = 'Could not analyze the resume: ' . $e->getMessage();
            }
        }

        return Inertia::render('ResumeCheck', [
            'hasResume'  => (bool) $profile->resume_path,
            'resumeName' => $profile->resume_name,
            'targetRole' => $profile->preferred_role,
            'report'     => $report,
            'error'      => $error,
        ]);
    }
}
