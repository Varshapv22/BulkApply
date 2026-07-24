<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IpRule;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('Admin/Security/Index', [
            'twoFactorEnabled' => $user->google2fa_enabled,
            'loginHistory' => LoginHistory::latest('created_at')->limit(50)->get(),
            'failedAttempts' => LoginHistory::where('successful', false)->where('created_at', '>=', now()->subDay())->count(),
            'suspendedUsers' => User::where('is_active', false)->get(['id', 'name', 'email']),
            'ipRules' => IpRule::latest()->get(),
            'activeSessions' => DB::table('sessions')->where('user_id', $user->id)->count(),
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $request->session()->put('2fa_setup_secret', $secret);

        return back()->with('status', 'Scan this into your authenticator app, then confirm with a code.')->with([
            'twoFactorSecret' => $secret,
            'twoFactorUri' => $google2fa->getQRCodeUrl('BulkApply', Auth::user()->email, $secret),
        ]);
    }

    public function confirmTwoFactor(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('2fa_setup_secret');

        if (!$secret) {
            return back()->with('error', 'Start the setup again.');
        }

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, preg_replace('/\s+/', '', $data['code']))) {
            return back()->with('error', 'Invalid code — please try again.');
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(4) . '-' . Str::random(4))->all();

        Auth::user()->forceFill([
            'google2fa_secret' => $secret,
            'google2fa_enabled' => true,
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        $request->session()->forget('2fa_setup_secret');
        AuditLog::record('security.2fa_enable', Auth::user());

        return back()->with('status', 'Two-factor authentication enabled.')->with('recoveryCodes', $recoveryCodes);
    }

    public function disableTwoFactor()
    {
        Auth::user()->forceFill([
            'google2fa_secret' => null,
            'google2fa_enabled' => false,
            'two_factor_recovery_codes' => null,
        ])->save();
        AuditLog::record('security.2fa_disable', Auth::user());

        return back()->with('status', 'Two-factor authentication disabled.');
    }

    public function storeIpRule(Request $request)
    {
        $data = $request->validate([
            'ip_or_cidr' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:allow,block'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $rule = IpRule::create($data);
        AuditLog::record('security.ip_rule_add', $rule, $data);

        return back()->with('status', 'IP rule added.');
    }

    public function destroyIpRule(IpRule $ipRule)
    {
        AuditLog::record('security.ip_rule_remove', $ipRule, ['ip_or_cidr' => $ipRule->ip_or_cidr, 'type' => $ipRule->type]);
        $ipRule->delete();

        return back()->with('status', 'IP rule removed.');
    }
}
