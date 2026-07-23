<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => AdminNotification::latest()->limit(100)->get(),
            'unreadCount' => AdminNotification::unread()->count(),
        ]);
    }

    public function recent()
    {
        return response()->json([
            'notifications' => AdminNotification::latest()->limit(10)->get(),
            'unread_count' => AdminNotification::unread()->count(),
        ]);
    }

    public function markRead(AdminNotification $notification)
    {
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead()
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked read.');
    }
}
