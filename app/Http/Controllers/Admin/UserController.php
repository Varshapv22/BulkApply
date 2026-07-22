<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobApplication;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles')->withCount(['resumes']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('is_active', $status === 'active');
        }

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];
        $query->orderBy(in_array($sort, $allowedSorts) ? $sort : 'created_at', $direction);

        $users = $query->paginate(20)->withQueryString()->through(fn ($u) => [
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'is_active'     => $u->is_active,
            'roles'         => $u->roles->pluck('name'),
            'resumes_count' => $u->resumes_count,
            'last_login_at' => $u->last_login_at,
            'created_at'    => $u->created_at,
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'roles'   => Role::pluck('name'),
            'filters' => $request->only('search', 'status', 'role', 'sort', 'direction'),
        ]);
    }

    public function show(User $user)
    {
        $profile = Profile::where('user_id', $user->id)->first();

        $applicationCounts = [
            'total'  => JobApplication::where('user_id', $user->id)->count(),
            'sent'   => JobApplication::where('user_id', $user->id)->where('status', JobApplication::STATUS_SENT)->count(),
            'failed' => JobApplication::where('user_id', $user->id)->where('status', JobApplication::STATUS_FAILED)->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'is_active'       => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'roles'           => $user->roles->pluck('name'),
                'last_login_at'   => $user->last_login_at,
                'last_login_ip'   => $user->last_login_ip,
                'created_at'      => $user->created_at,
            ],
            'profile' => $profile ? [
                'full_name'          => $profile->full_name,
                'email'              => $profile->email,
                'phone'              => $profile->phone,
                'location'           => $profile->location,
                'preferred_role'     => $profile->preferred_role,
                'preferred_sites'    => $profile->preferred_sites,
                'skills'             => $profile->skills,
                'has_documents'      => $profile->hasDocuments(),
                'has_mail_credentials' => $profile->hasMailCredentials(),
                'mail_username'      => $profile->mail_username,
                'max_emails_per_hour' => $profile->max_emails_per_hour,
                'followup_days'      => $profile->followup_days,
                'webhook_url'        => $profile->webhook_url,
            ] : null,
            'resumes' => $user->resumes()->get(['id', 'name', 'is_default', 'created_at']),
            'applicationCounts' => $applicationCounts,
            'allRoles' => Role::pluck('name'),
            'currentPlan' => $user->activePlan()?->only('id', 'name'),
            'plans' => Plan::where('is_active', true)->get(['id', 'name', 'price']),
        ]);
    }

    public function toggleActive(User $user)
    {
        $user->forceFill(['is_active' => !$user->is_active])->save();
        AuditLog::record($user->is_active ? 'user.activate' : 'user.suspend', $user);

        return back()->with('status', $user->is_active ? 'User activated.' : 'User suspended.');
    }

    public function verifyEmail(User $user)
    {
        $user->forceFill(['email_verified_at' => now()])->save();
        AuditLog::record('user.verify_email', $user);

        return back()->with('status', 'Email marked as verified.');
    }

    public function resetPassword(User $user)
    {
        $password = Str::password(16);
        $user->forceFill(['password' => $password])->save();
        AuditLog::record('user.reset_password', $user);

        return back()->with('status', "New password for {$user->email}: {$password}");
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles(($data['role'] ?? null) ? [$data['role']] : []);
        AuditLog::record('user.update_role', $user, ['role' => $data['role'] ?? null]);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        AuditLog::record('user.delete', $user, ['email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function loginAs(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You are already logged in as this user.');
        }

        AuditLog::record('user.login_as', $user);
        $request->session()->put('impersonator_id', Auth::id());
        Auth::login($user);

        return redirect('/dashboard')->with('status', "Now viewing as {$user->name}.");
    }

    public function returnToAdmin(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');

        if (!$adminId) {
            return redirect('/dashboard');
        }

        Auth::loginUsingId($adminId);

        return redirect()->route('admin.users.index')->with('status', 'Returned to admin account.');
    }

    public function export(): StreamedResponse
    {
        $users = User::with('roles')->get();

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Status', 'Roles', 'Last Login', 'Joined']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->is_active ? 'Active' : 'Suspended',
                    $user->roles->pluck('name')->implode(', '),
                    $user->last_login_at,
                    $user->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'users.csv', ['Content-Type' => 'text/csv']);
    }
}
