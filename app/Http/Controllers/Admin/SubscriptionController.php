<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PlanUpgraded;
use App\Notifications\SubscriptionCancelled;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::query()->with(['user:id,name,email', 'plan:id,name,price,duration_days']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only('status', 'search'),
        ]);
    }

    public function assign(Request $request, User $user)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
        ]);

        AuditLog::record('subscription.assign', $user, ['plan_id' => $plan->id]);
        $user->notify(new PlanUpgraded($plan));

        return back()->with('status', 'Plan assigned to ' . $user->name . '.');
    }

    public function cancel(User $user)
    {
        $planName = $user->activeSubscription()?->plan?->name;

        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);
        AuditLog::record('subscription.cancel', $user);
        $user->notify(new SubscriptionCancelled($planName));

        return back()->with('status', 'Subscription cancelled for ' . $user->name . '.');
    }
}
