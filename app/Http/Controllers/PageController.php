<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\Plan;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return Inertia::render('Page', [
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'plans' => $slug === 'pricing'
                ? Plan::where('is_active', true)->orderBy('duration_days')->get(['name', 'price', 'duration_days'])
                : null,
        ]);
    }
}
