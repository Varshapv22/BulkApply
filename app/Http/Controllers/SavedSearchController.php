<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Services\SiteJobService;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function store(Request $request, SiteJobService $siteService)
    {
        $data = $request->validate([
            'role'     => ['required_without:site', 'nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'site'     => ['nullable', 'string', 'max:2048'],
        ]);

        $role = trim($data['role'] ?? '');
        $location = trim($data['location'] ?? '');
        $site = trim($data['site'] ?? '');

        if ($site !== '' && $siteService->isKnownPlatform($site)) {
            return back()->with('error', "Alerts aren't supported for {$site} — it shows aggregated web results, not real listings from that platform, so \"new job\" alerts would be misleading. Try a tech park, a company name/URL, or leave the site blank to search everywhere.");
        }

        $existing = $request->user()->savedSearches()->active()
            ->whereRaw('LOWER(role) = ?', [mb_strtolower($role)])
            ->whereRaw("LOWER(COALESCE(location, '')) = ?", [mb_strtolower($location)])
            ->whereRaw("LOWER(COALESCE(site, '')) = ?", [mb_strtolower($site)])
            ->first();

        if ($existing) {
            return back()->with('status', 'You already have this search saved.');
        }

        if ($request->user()->savedSearches()->active()->count() >= SavedSearch::MAX_PER_USER) {
            return back()->with('error', 'You can have up to ' . SavedSearch::MAX_PER_USER . ' active saved searches. Pause or delete one before adding another.');
        }

        $request->user()->savedSearches()->create([
            'role' => $role,
            'location' => $location ?: null,
            'site' => $site ?: null,
        ]);

        return back()->with('status', 'Search saved — we\'ll notify you when new listings appear.');
    }

    public function toggleActive(Request $request, SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== $request->user()->id) abort(403);

        $savedSearch->update(['is_active' => !$savedSearch->is_active]);

        return back()->with('status', $savedSearch->is_active ? 'Alerts resumed.' : 'Alerts paused.');
    }

    public function destroy(Request $request, SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== $request->user()->id) abort(403);

        $savedSearch->delete();

        return back()->with('status', 'Saved search deleted.');
    }
}
