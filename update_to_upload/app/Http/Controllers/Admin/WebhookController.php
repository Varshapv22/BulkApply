<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class WebhookController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Webhooks/Index', [
            'logs' => WebhookLog::latest()->limit(50)->get(),
        ]);
    }

    public function retry(WebhookLog $log)
    {
        try {
            $response = Http::timeout(10)->post($log->url, $log->payload ?? []);

            WebhookLog::create([
                'job_application_id' => $log->job_application_id,
                'url' => $log->url,
                'payload' => $log->payload,
                'response_code' => $response->status(),
                'success' => $response->successful(),
            ]);

            return back()->with('status', $response->successful() ? 'Webhook retried successfully.' : "Webhook retried but failed (HTTP {$response->status()}).");
        } catch (\Throwable $e) {
            WebhookLog::create([
                'job_application_id' => $log->job_application_id,
                'url' => $log->url,
                'payload' => $log->payload,
                'response_code' => null,
                'success' => false,
                'error' => substr($e->getMessage(), 0, 500),
            ]);

            return back()->with('error', 'Webhook retry failed: ' . $e->getMessage());
        }
    }
}
