<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('price')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Plan::create($data);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request);

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('status', $plan->is_active ? 'Plan enabled.' : 'Plan disabled.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with subscribers. Disable it instead.');
        }

        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'email_limit' => ['nullable', 'integer', 'min:0'],
            'resume_limit' => ['nullable', 'integer', 'min:0'],
            'daily_application_limit' => ['nullable', 'integer', 'min:0'],
            'queue_priority' => ['required', 'integer', 'min:0'],
            'storage_limit_mb' => ['nullable', 'integer', 'min:0'],
            'chrome_extension_access' => ['boolean'],
            'ats_checker_access' => ['boolean'],
            'api_access' => ['boolean'],
        ]);
    }
}
