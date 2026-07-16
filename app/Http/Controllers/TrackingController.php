<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;

class TrackingController extends Controller
{
    /**
     * Serve a 1x1 transparent pixel and record the open.
     */
    public function pixel(string $trackingId)
    {
        $job = JobApplication::where('tracking_id', $trackingId)->first();

        if ($job && !$job->opened_at) {
            $job->update(['opened_at' => now()]);
        }

        // 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Track a link click and redirect to the actual URL.
     */
    public function click(string $trackingId)
    {
        $job = JobApplication::where('tracking_id', $trackingId)->first();

        if (!$job) {
            abort(404);
        }

        if (!$job->clicked_at) {
            $job->update(['clicked_at' => now()]);
        }

        return redirect($job->job_url ?: '/');
    }
}
