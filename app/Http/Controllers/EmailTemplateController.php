<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return view('templates', [
            'templates' => EmailTemplate::latest()->get(),
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
            EmailTemplate::where('is_default', true)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        EmailTemplate::create($data);

        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        if ($request->boolean('is_default')) {
            EmailTemplate::where('is_default', true)->where('id', '!=', $template->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        } else {
            $data['is_default'] = false;
        }

        $template->update($data);

        return redirect()->route('templates.index')->with('status', 'Template updated.');
    }

    public function destroy(EmailTemplate $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
