<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('admin:id,name,email');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->latest('created_at')->paginate(30)->withQueryString()->through(fn ($log) => [
            'id' => $log->id,
            'action' => $log->action,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'changes' => $log->changes,
            'ip' => $log->ip,
            'admin' => $log->admin ? ['name' => $log->admin->name, 'email' => $log->admin->email] : null,
            'created_at' => $log->created_at,
        ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only('action'),
        ]);
    }
}
