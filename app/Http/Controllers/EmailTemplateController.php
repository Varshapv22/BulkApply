<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Templates', [
            'templates' => EmailTemplate::where('user_id', Auth::id())->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        if ($request->boolean('is_default')) {
            EmailTemplate::where('user_id', Auth::id())->where('is_default', true)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        EmailTemplate::create($data + ['user_id' => Auth::id()]);

        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function update(Request $request, EmailTemplate $template)
    {
        abort_if($template->user_id !== Auth::id(), 404);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        if ($request->boolean('is_default')) {
            EmailTemplate::where('user_id', Auth::id())->where('is_default', true)->where('id', '!=', $template->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        } else {
            $data['is_default'] = false;
        }

        $template->update($data);

        return redirect()->route('templates.index')->with('status', 'Template updated.');
    }

    public function destroy(EmailTemplate $template)
    {
        abort_if($template->user_id !== Auth::id(), 404);

        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
