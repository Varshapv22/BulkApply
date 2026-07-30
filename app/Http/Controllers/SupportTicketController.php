<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()->supportTickets()
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'subject' => $t->subject,
                'message' => $t->message,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);

        return Inertia::render('Support/Tickets/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'type' => $ticket->type,
                'subject' => $ticket->subject,
                'message' => $ticket->message,
                'status' => $ticket->status,
                'attachment_name' => $ticket->attachment_name,
                'created_at' => $ticket->created_at,
            ],
            'replies' => $ticket->replies->map(fn ($r) => [
                'id' => $r->id,
                'is_admin' => $r->is_admin,
                'author_name' => $r->author_name,
                'message' => $r->message,
                'created_at' => $r->created_at,
            ]),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'user_id' => Auth::id(),
            'is_admin' => false,
            'author_name' => Auth::user()->name,
            'message' => $data['message'],
        ]);

        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        AdminNotification::log(
            'support_reply',
            "{$ticket->name} replied to ticket #{$ticket->id}" . ($ticket->subject ? ": {$ticket->subject}" : '.'),
            ['ticket_id' => $ticket->id]
        );

        return back()->with('status', 'Reply sent.');
    }

    public function attachment(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id(), 403);

        if (!$ticket->attachment_path || !Storage::exists($ticket->attachment_path)) {
            abort(404);
        }

        return Storage::download($ticket->attachment_path, $ticket->attachment_name ?: 'attachment');
    }
}
