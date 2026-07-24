<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Plan;
use App\Models\PlanPaymentRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();

        // upiId, upiPayeeName, and pendingPlanIds arrive via the shared Inertia props (HandleInertiaRequests).
        return Inertia::render('Billing', [
            'plans' => Plan::where('is_active', true)->orderBy('duration_days')->get(),
            'currentPlanId' => $subscription?->plan_id,
            'subscription' => $subscription ? [
                'status'    => $subscription->status,
                'starts_at' => optional($subscription->starts_at)->toDateString(),
                'ends_at'   => optional($subscription->ends_at)->toDateString(),
            ] : null,
        ]);
    }

    public function submitPayment(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'transaction_ref' => ['required', 'string', 'max:100'],
            'screenshot' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($data['plan_id']);

        if ($user->planPaymentRequests()->pending()->where('plan_id', $plan->id)->exists()) {
            return back()->with('error', "You already have a pending payment request for {$plan->name}.");
        }

        $screenshotPath = $request->hasFile('screenshot')
            ? $request->file('screenshot')->store('payment-screenshots')
            : null;

        PlanPaymentRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'transaction_ref' => $data['transaction_ref'],
            'screenshot_path' => $screenshotPath,
            'status' => PlanPaymentRequest::STATUS_PENDING,
        ]);

        AdminNotification::log(
            'plan_payment_request',
            "{$user->name} ({$user->email}) paid for the \"{$plan->name}\" plan via UPI — ref {$data['transaction_ref']}.",
            ['user_id' => $user->id, 'plan_id' => $plan->id]
        );

        return back()->with('status', "Payment submitted — an admin will verify it and activate {$plan->name} shortly.");
    }
}
