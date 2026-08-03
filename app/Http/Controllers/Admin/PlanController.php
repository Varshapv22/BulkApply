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

        $plan = null;
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$plan) {
            if (!empty($data['is_trial_plan'])) {
                Plan::where('is_trial_plan', true)->update(['is_trial_plan' => false]);
            }
            $plan = Plan::create($data);
        });
        AuditLog::record('plan.create', $plan, $data);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $plan) {
            if (!empty($data['is_trial_plan'])) {
                Plan::where('is_trial_plan', true)->where('id', '!=', $plan->id)->update(['is_trial_plan' => false]);
            }
            $plan->update($data);
        });
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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'resume_limit' => ['nullable', 'integer', 'min:0'],
            'cover_letter_limit' => ['nullable', 'integer', 'min:0'],
            'is_trial_plan' => ['nullable', 'boolean'],
        ]);

        $data['is_trial_plan'] = (bool) ($data['is_trial_plan'] ?? false);
        $data['email_limit'] = null;

        return $data;
    }
}
