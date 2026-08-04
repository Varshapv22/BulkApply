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
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FreeAccessController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::query()
            ->where('source', Subscription::SOURCE_ADMIN_GRANT)
            ->with(['user:id,name,email', 'plan:id,name,price,duration_days', 'grantedBy:id,name']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        $grants = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/FreeAccess/Index', [
            'grants' => $grants,
            'plans' => Plan::where('is_active', true)->get(['id', 'name', 'price', 'duration_days']),
            'filters' => $request->only('status', 'search'),
        ]);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->string('q')->toString();

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function grant(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $plan = Plan::findOrFail($data['plan_id']);

        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
            'source' => Subscription::SOURCE_ADMIN_GRANT,
            'granted_by_user_id' => Auth::id(),
        ]);

        AuditLog::record('subscription.grant_free', $user, ['plan_id' => $plan->id]);
        $user->notify(new PlanUpgraded($plan));

        return back()->with('status', "Free \"{$plan->name}\" access granted to {$user->name}.");
    }

    public function revoke(Subscription $subscription)
    {
        $user = $subscription->user;
        $planName = $subscription->plan?->name;

        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        AuditLog::record('subscription.revoke_free', $user);
        $user?->notify(new SubscriptionCancelled($planName));

        return back()->with('status', 'Free access revoked' . ($user ? " for {$user->name}." : '.'));
    }
}
