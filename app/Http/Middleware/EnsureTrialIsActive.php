<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side backstop for the 7-day free trial. The sidebar's
 * TrialExpiredModal already blocks the UI once the trial has lapsed, but
 * that's cosmetic — a direct request (a saved link, an API client, JS
 * disabled) could otherwise still reach these routes. Once trial_ends_at has
 * passed with no active subscription, every route except billing (to view
 * plans and pay) is redirected there; logout stays reachable because its
 * route sits outside the "auth" group this middleware is attached to.
 */
class EnsureTrialIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->getRoleNames()->isNotEmpty() || $user->activeSubscription()) {
            return $next($request);
        }

        $trialEndsAt = $user->trial_ends_at;
        if (! $trialEndsAt || now()->lessThanOrEqualTo($trialEndsAt)) {
            return $next($request);
        }

        if ($request->routeIs('billing.*')) {
            return $next($request);
        }

        return redirect()->route('billing.index');
    }
}
