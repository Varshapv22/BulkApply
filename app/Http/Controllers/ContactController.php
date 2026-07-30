<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        return Inertia::render('Contact', [
            'user' => Auth::check() ? ['name' => Auth::user()->name, 'email' => Auth::user()->email] : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:contact,feedback,feature_request,bug_report'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $attachment = $request->file('attachment');
        unset($data['attachment']);

        $ticket = SupportTicket::create([
            ...$data,
            'attachment_path' => $attachment?->store('support-attachments'),
            'attachment_name' => $attachment?->getClientOriginalName(),
            'user_id' => Auth::id(),
        ]);

        AdminNotification::log(
            'support_new_ticket',
            "New {$ticket->type} ticket from {$ticket->name} ({$ticket->email})" . ($ticket->subject ? ": {$ticket->subject}" : '.'),
            ['ticket_id' => $ticket->id]
        );

        return back()->with('status', "Thanks — we've received your message and will get back to you soon.");
    }
}
