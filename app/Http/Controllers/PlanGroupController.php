<?php

namespace App\Http\Controllers;

use App\Models\MonthlyPlan;
use App\Models\PlanGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanGroupController extends Controller
{
    public function store(Request $request, MonthlyPlan $plan): RedirectResponse
    {
        $this->authorize('manage', $plan);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        PlanGroup::create([
            'monthly_plan_id' => $plan->id,
            'name' => $data['name'],
            'position' => ($plan->groups()->max('position') ?? 0) + 1,
        ]);

        return back();
    }

    public function destroy(PlanGroup $planGroup): RedirectResponse
    {
        $this->authorize('manage', $planGroup->monthlyPlan);

        $planGroup->delete();

        return back();
    }
}
