<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function assign(Request $request, User $user)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $data['plan_id'],
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return back()->with('status', 'Plan assigned to ' . $user->name . '.');
    }

    public function cancel(User $user)
    {
        $user->subscriptions()->active()->update(['status' => Subscription::STATUS_CANCELLED]);

        return back()->with('status', 'Subscription cancelled for ' . $user->name . '.');
    }
}
