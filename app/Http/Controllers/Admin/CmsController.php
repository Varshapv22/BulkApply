<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CmsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Cms/Index', [
            'pages' => CmsPage::orderBy('slug')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        CmsPage::create($data + ['updated_by' => Auth::id()]);

        return back()->with('status', 'Page created.');
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $this->validated($request, $page);

        $page->update($data + ['updated_by' => Auth::id()]);

        return back()->with('status', 'Page updated.');
    }

    public function destroy(CmsPage $page)
    {
        $page->delete();

        return back()->with('status', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);
    }
}
