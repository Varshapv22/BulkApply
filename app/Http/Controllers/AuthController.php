<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\LoginHistory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ok = Auth::attempt($credentials, $request->boolean('remember'));

        LoginHistory::create([
            'email' => $credentials['email'],
            'user_id' => $ok ? Auth::id() : User::where('email', $credentials['email'])->value('id'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'successful' => $ok,
            'created_at' => now(),
        ]);

        if (!$ok) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account has been suspended.'])->onlyInput('email');
        }

        if ($user->google2fa_enabled) {
            Auth::logout();
            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_remember', $request->boolean('remember'));
            return redirect('/2fa-challenge');
        }

        $this->completeLogin($request, $user);

        return redirect()->intended($user->getRoleNames()->isNotEmpty() ? '/admin' : '/dashboard');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect('/login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function verifyTwoFactorChallenge(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');
        if (!$userId) {
            return redirect('/login');
        }

        $data = $request->validate(['code' => ['required', 'string']]);
        $user = User::findOrFail($userId);

        $valid = (new Google2FA())->verifyKey($user->google2fa_secret, preg_replace('/\s+/', '', $data['code']));

        // A recovery code also works, once — consumed on use.
        if (!$valid && $user->two_factor_recovery_codes) {
            $codes = $user->two_factor_recovery_codes;
            $idx = array_search($data['code'], $codes, true);
            if ($idx !== false) {
                unset($codes[$idx]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $valid = true;
            }
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $remember = $request->session()->pull('2fa_remember', false);
        $request->session()->forget('2fa_user_id');

        Auth::login($user, $remember);
        $this->completeLogin($request, $user);

        return redirect()->intended($user->getRoleNames()->isNotEmpty() ? '/admin' : '/dashboard');
    }

    private function completeLogin(Request $request, User $user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $request->session()->regenerate();
    }

    public function showRegister()
    {
        if (!Setting::get('registration_enabled', true)) {
            return redirect('/login')->withErrors(['email' => 'New registrations are currently closed.']);
        }

        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        if (!Setting::get('registration_enabled', true)) {
            return back()->withErrors(['email' => 'New registrations are currently closed.']);
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:' . Setting::get('password_min_length', 8), 'confirmed'],
        ]);

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => $data['password'],
            'trial_ends_at' => now()->addDays(7),
        ]);

        AdminNotification::log('new_registration', "New user registered: {$user->name} ({$user->email})", ['user_id' => $user->id]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function updateAccount(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', 'Password changed successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
