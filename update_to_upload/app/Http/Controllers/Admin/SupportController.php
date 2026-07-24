<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $query->latest()->get(),
            'filters' => $request->only('status', 'type'),
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,resolved']]);

        $ticket->update($data);

        return back()->with('status', 'Ticket updated.');
    }
}
