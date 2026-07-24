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
                        $request->user()->only('id', 'name', 'email', 'is_active'),
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
            // Every price in the app renders with this symbol — set in Admin > Settings > General > Currency.
            'currencySymbol' => fn () => \App\Models\Setting::currencySymbol(),
            // Free trial status — null for admins and subscribed users.
            'trial' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty()) {
                    return null;
                }
                if ($user->activeSubscription()) {
                    return null;
                }
                // trial_ends_at is set by admin (defaults to 7 days from registration).
                $trialEndsAt = $user->trial_ends_at;
                if (! $trialEndsAt) {
                    return null; // no trial set — treat as unrestricted
                }
                $expired  = now()->greaterThan($trialEndsAt);
                $daysLeft = $expired ? 0 : (int) now()->diffInDays($trialEndsAt);

                return [
                    'expired'       => $expired,
                    'days_left'     => $daysLeft,
                    'trial_ends_at' => $trialEndsAt->toDateString(),
                ];
            },
            // Whether the logged-in user still needs to fill in their core profile
            // details (name, phone, location, resume) — drives the onboarding nudge.
            'needsOnboarding' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty()) {
                    return false;
                }

                $profile = \App\Models\Profile::where('user_id', $user->id)->first();

                return blank($profile?->full_name)
                    || blank($profile?->phone)
                    || blank($profile?->location)
                    || ! $user->resumes()->exists();
            },
            // Unread count for the topbar notification bell — admins see AdminNotification
            // (platform-wide events), regular users see their own database notifications.
            'unreadNotifications' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return 0;
                }

                return $user->getRoleNames()->isNotEmpty()
                    ? \App\Models\AdminNotification::unread()->count()
                    : $user->unreadNotifications()->count();
            },
            // Active plans — shared so the upgrade modal can list them without a separate request.
            'plans' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty() || $user->activeSubscription()) {
                    return [];
                }

                return \App\Models\Plan::where('is_active', true)
                    ->orderBy('duration_days')
                    ->get(['id', 'name', 'price', 'duration_days'])
                    ->toArray();
            },
            // UPI payment details for the trial-expired paywall's pay-and-verify flow.
            // Admin > Settings > Billing overrides the UPI_ID / UPI_PAYEE_NAME env defaults.
            'upiId' => fn () => \App\Models\Setting::get('upi_id') ?: config('services.upi.id', ''),
            'upiPayeeName' => fn () => \App\Models\Setting::get('upi_payee_name') ?: config('services.upi.payee_name', 'BulkApply'),
            'pendingPlanIds' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty()) {
                    return [];
                }

                return $user->planPaymentRequests()->pending()->pluck('plan_id');
            },
        ]);
    }
}
