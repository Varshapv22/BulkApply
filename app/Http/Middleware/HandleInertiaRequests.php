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
                'status'       => fn () => $request->session()->get('status'),
                'error'        => fn () => $request->session()->get('error'),
                'gmail_status' => fn () => $request->session()->get('gmail_status'),
                'gmail_error'  => fn () => $request->session()->get('gmail_error'),
            ],
            // One-shot secrets shown once right after generation (2FA setup) — never persisted to the page beyond this single request.
            'twoFactorSecret' => fn () => $request->session()->get('twoFactorSecret'),
            'twoFactorUri' => fn () => $request->session()->get('twoFactorUri'),
            'recoveryCodes' => fn () => $request->session()->get('recoveryCodes'),
            'impersonating' => $request->session()->has('impersonator_id'),
            // Unread recruiter replies — drives the count badge on the sidebar's Replies item.
            'unreadReplies' => fn () => $request->user()
                ? \App\Models\GmailReply::where('user_id', $request->user()->id)->where('is_read', false)->count()
                : 0,
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
            // Active plans for the trial-expired paywall modal, shared so it can list them
            // without a separate request. Deliberately named "upgradePlans", not "plans" —
            // EnsureTrialIsActive forces every expired-trial user onto /billing, and
            // BillingController renders its own "plans" prop (all plans, including Free).
            // A same-named page prop silently wins over a shared one in Inertia, so using
            // "plans" here let the Free plan leak into the modal on the one page these
            // users can actually reach.
            'upgradePlans' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->getRoleNames()->isNotEmpty() || $user->activeSubscription()) {
                    return [];
                }

                // Excludes free/₹0 plans: offering a "free trial" plan to a user whose
                // trial already ended makes no sense — only real paid plans belong here.
                return \App\Models\Plan::where('is_active', true)
                    ->where('price', '>', 0)
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
