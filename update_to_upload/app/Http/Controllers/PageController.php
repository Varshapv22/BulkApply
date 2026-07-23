<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return Inertia::render('Page', [
            'title' => $page->title,
            'content' => $page->content,
        ]);
    }
}
