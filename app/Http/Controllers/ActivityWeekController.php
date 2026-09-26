<?php

namespace App\Http\Controllers;

use App\Models\ActivityWeek;
use Illuminate\Http\RedirectResponse;

class ActivityWeekController extends Controller
{
    public function toggle(ActivityWeek $activityWeek): RedirectResponse
    {
        $this->authorize('report', $activityWeek->activity);

        if ($activityWeek->isCompleted()) {
            $activityWeek->update(['completed_at' => null, 'completed_by' => null]);
        } else {
            $activityWeek->update(['completed_at' => now(), 'completed_by' => request()->user()->id]);
        }

        return back();
    }
}
