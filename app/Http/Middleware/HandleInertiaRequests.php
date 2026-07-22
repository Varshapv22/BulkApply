<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default to every page.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user()
                    ? array_merge(
                        $request->user()->only('id', 'name', 'email'),
                        ['isAdmin' => $request->user()->getRoleNames()->isNotEmpty()]
                    )
                    : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error'  => fn () => $request->session()->get('error'),
            ],
            // One-shot secrets shown once right after generation (2FA setup) — never persisted to the page beyond this single request.
            'twoFactorSecret' => fn () => $request->session()->get('twoFactorSecret'),
            'twoFactorUri' => fn () => $request->session()->get('twoFactorUri'),
            'recoveryCodes' => fn () => $request->session()->get('recoveryCodes'),
            'impersonating' => $request->session()->has('impersonator_id'),
            // The address applications are sent FROM — used to open the correct
            // Gmail account (via ?authuser=) regardless of the browser's default.
            'mailFrom' => config('mail.from.address'),
            // Free trial status — null for admins and subscribed users.
            'trial' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty()) {
                    return null;
                }
                if ($user->activeSubscription()) {
                    return null;
                }
                $trialEndsAt = $user->created_at->addDays(7);
                $expired     = now()->greaterThan($trialEndsAt);
                $daysLeft    = $expired ? 0 : (int) now()->diffInDays($trialEndsAt);

                return [
                    'expired'       => $expired,
                    'days_left'     => $daysLeft,
                    'trial_ends_at' => $trialEndsAt->toDateString(),
                ];
            },
            // Active plans — shared so the upgrade modal can list them without a separate request.
            'plans' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty() || $user->activeSubscription()) {
                    return [];
                }

                return \App\Models\Plan::where('is_active', true)
                    ->orderBy('price')
                    ->get(['id', 'name', 'price', 'billing_interval', 'email_limit', 'resume_limit', 'daily_application_limit', 'chrome_extension_access', 'ats_checker_access'])
                    ->toArray();
            },
        ]);
    }
}
