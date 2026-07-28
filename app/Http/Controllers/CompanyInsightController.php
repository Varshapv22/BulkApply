<?php

namespace App\Http\Controllers;

use App\Services\CompanyInsightService;
use Illuminate\Http\Request;

class CompanyInsightController extends Controller
{
    /**
     * Employee LinkedIn profiles + culture/review links for one company.
     * Fetched over JSON from the Search and Applications pages.
     */
    public function show(Request $request, CompanyInsightService $service)
    {
        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'role'    => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:2048'],
        ]);

        return response()->json(
            $service->forCompany($data['company'], $data['website'] ?? null, $data['role'] ?? null)
        );
    }
}
