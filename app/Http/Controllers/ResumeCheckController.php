<?php

namespace App\Http\Controllers;

use App\Models\FeatureFlag;
use App\Models\Profile;
use App\Services\ResumeAtsAnalyzer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResumeCheckController extends Controller
{
    /**
     * Analyze the uploaded resume for ATS friendliness.
     */
    public function index(ResumeAtsAnalyzer $analyzer)
    {
        if (!FeatureFlag::enabled('feature.ats_checker')) {
            abort(403, 'The Resume ATS Checker is currently disabled by the administrator.');
        }

        $profile = Profile::current();

        $resumePath = $profile->resume_path;
        $resumeName = $profile->resume_name;

        // Fall back to the default resume in the user's resume library if the
        // legacy single-resume profile field was never filled in.
        if (!$resumePath) {
            $resume = Auth::user()->resumes()->where('is_default', true)->first()
                ?? Auth::user()->resumes()->latest()->first();
            if ($resume) {
                $resumePath = $resume->file_path;
                $resumeName = $resume->name;
            }
        }

        $report = null;
        $error = null;

        if ($resumePath && Storage::exists($resumePath)) {
            // Copy to a local temp file since the analyzer needs a real filesystem
            // path (ZipArchive/PDF parser) — Storage::path() doesn't work once the
            // default disk is a cloud disk like S3, which has no local path.
            $tmpPath = tempnam(sys_get_temp_dir(), 'resume_');
            try {
                file_put_contents($tmpPath, Storage::get($resumePath));
                $report = $analyzer->analyze(
                    $tmpPath,
                    $resumeName ?: basename($resumePath),
                    $profile->preferred_role ?? ''
                );
            } catch (\Throwable $e) {
                $error = 'Could not analyze the resume: ' . $e->getMessage();
            } finally {
                @unlink($tmpPath);
            }
        }

        return Inertia::render('ResumeCheck', [
            'hasResume'  => (bool) $resumePath,
            'resumeName' => $resumeName,
            'targetRole' => $profile->preferred_role,
            'report'     => $report,
            'error'      => $error,
        ]);
    }
}
