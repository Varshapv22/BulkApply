<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlanPaymentRequest;
use App\Models\Subscription;
use App\Notifications\PlanPaymentRejected;
use App\Notifications\PlanUpgraded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PlanPaymentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanPaymentRequest::query()->with(['user:id,name,email', 'plan:id,name,price,duration_days', 'reviewer:id,name']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/PaymentRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only('status'),
        ]);
    }

    public function screenshot(PlanPaymentRequest $paymentRequest)
    {
        if (!$paymentRequest->screenshot_path || !Storage::exists($paymentRequest->screenshot_path)) {
            abort(404);
        }

        return Storage::response($paymentRequest->screenshot_path);
    }

    public function approve(Request $request, PlanPaymentRequest $paymentRequest)
    {
        if ($paymentRequest->status !== PlanPaymentRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $user = $paymentRequest->user;
        $plan = $paymentRequest->plan;

        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
        ]);

        $paymentRequest->update([
            'status' => PlanPaymentRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record('plan_payment_request.approve', $user, ['plan_id' => $plan->id, 'payment_request_id' => $paymentRequest->id]);
        $user->notify(new PlanUpgraded($plan));

        return back()->with('status', "Payment verified — {$user->name} is now on the {$plan->name} plan.");
    }

    public function reject(Request $request, PlanPaymentRequest $paymentRequest)
    {
        if ($paymentRequest->status !== PlanPaymentRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentRequest->update([
            'status' => PlanPaymentRequest::STATUS_REJECTED,
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record('plan_payment_request.reject', $paymentRequest->user, ['plan_id' => $paymentRequest->plan_id, 'payment_request_id' => $paymentRequest->id]);
        $paymentRequest->user->notify(new PlanPaymentRejected($paymentRequest->plan, $data['admin_note'] ?? null));

        return back()->with('status', 'Payment request rejected.');
    }
}
