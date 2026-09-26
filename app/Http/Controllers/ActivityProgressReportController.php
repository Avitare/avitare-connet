<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityProgressReportAttachment;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityProgressReportController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('report', $activity);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
        ]);

        if (
            $data['completed']
            && SystemSetting::current()->require_weeks_completed_to_mark_activity_done
            && ! $activity->allPlannedWeeksCompleted()
        ) {
            throw ValidationException::withMessages([
                'completed' => 'Marca todas las semanas planificadas antes de dar la actividad por cumplida.',
            ]);
        }

        $activity->progressReports()->create([
            'value' => $data['completed'] ? $activity->target() : 0,
            'reported_by' => $request->user()->id,
        ]);

        return back();
    }

    public function downloadAttachment(ActivityProgressReportAttachment $attachment): StreamedResponse
    {
        $plan = $attachment->progressReport->activity->planGroup->monthlyPlan;

        $this->authorize('view', $plan);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
