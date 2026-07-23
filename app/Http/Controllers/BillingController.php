<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();

        return Inertia::render('Billing', [
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'currentPlanId' => $subscription?->plan_id,
            'subscription' => $subscription ? [
                'status'    => $subscription->status,
                'starts_at' => optional($subscription->starts_at)->toDateString(),
                'ends_at'   => optional($subscription->ends_at)->toDateString(),
            ] : null,
        ]);
    }

    public function requestUpgrade(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($data['plan_id']);

        AdminNotification::log(
            'plan_upgrade_request',
            "{$user->name} ({$user->email}) requested the \"{$plan->name}\" plan.",
            ['user_id' => $user->id, 'plan_id' => $plan->id]
        );

        return back()->with('status', "Request sent — an admin will upgrade your account to {$plan->name} shortly.");
    }
}
