<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('duration_days')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $plan = Plan::create($data);
        AuditLog::record('plan.create', $plan, $data);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request);

        $plan->update($data);
        AuditLog::record('plan.update', $plan, $data);

        return back()->with('status', 'Plan updated.');
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        AuditLog::record($plan->is_active ? 'plan.enable' : 'plan.disable', $plan);

        return back()->with('status', $plan->is_active ? 'Plan enabled.' : 'Plan disabled.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with subscribers. Disable it instead.');
        }

        AuditLog::record('plan.delete', $plan, ['name' => $plan->name]);
        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    // Plans differ only by name, price, and duration — every plan gives full, unlimited access.
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
        ]);

        return array_merge($data, [
            'email_limit' => null,
            'resume_limit' => null,
        ]);
    }
}
