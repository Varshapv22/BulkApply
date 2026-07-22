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
        ]);
    }
}
