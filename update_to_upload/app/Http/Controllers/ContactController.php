<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        return Inertia::render('Contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:contact,feedback,feature_request,bug_report'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicket::create($data + ['user_id' => Auth::id()]);

        return back()->with('status', "Thanks — we've received your message and will get back to you soon.");
    }
}
