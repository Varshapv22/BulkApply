<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobSourceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/JobSources/Index', [
            'sources' => FeatureFlag::where('key', 'like', 'source.%')->orderBy('priority')->get(),
        ]);
    }

    public function toggle(FeatureFlag $source)
    {
        $source->update(['enabled' => !$source->enabled]);

        return back()->with('status', "{$source->label} " . ($source->enabled ? 'enabled' : 'disabled') . '.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer', 'exists:feature_flags,id']]);

        foreach ($data['order'] as $index => $id) {
            FeatureFlag::where('id', $id)->update(['priority' => $index]);
        }

        return back()->with('status', 'Priority order updated.');
    }
}
