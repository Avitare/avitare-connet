<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\PlanGroup;
use App\Models\SystemSetting;
use App\Support\CalendarWeeks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function store(Request $request, PlanGroup $planGroup): RedirectResponse
    {
        $plan = $planGroup->monthlyPlan;

        $this->authorize('manage', $plan);

        $maxWeek = CalendarWeeks::weeksInMonth($plan->period->year, $plan->period->month);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'weeks' => ['required', 'array', 'min:1'],
            'weeks.*' => ['integer', 'distinct', "between:1,{$maxWeek}"],
            'progress_type' => ['required', 'in:porcentaje,meta_numerica'],
            'numeric_goal_target' => ['required_if:progress_type,meta_numerica', 'nullable', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'deliverable' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $weeks = $data['weeks'];
        unset($data['weeks']);

        DB::transaction(function () use ($planGroup, $data, $weeks) {
            $activity = Activity::create([
                'plan_group_id' => $planGroup->id,
                ...$data,
            ]);

            foreach ($weeks as $week) {
                $activity->weeks()->create(['week_number' => $week]);
            }
        });

        return back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('manage', $activity->planGroup->monthlyPlan);

        $activity->delete();

        return back();
    }

    public function close(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('close', $activity);

        if (SystemSetting::current()->require_deliverable_to_close_activity && ! $activity->hasDeliverable()) {
            throw ValidationException::withMessages([
                'deliverable' => 'Sube un entregable antes de cerrar la actividad.',
            ]);
        }

        $activity->update([
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        return back();
    }

    public function reopen(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('close', $activity);

        $activity->update([
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return back();
    }

    public function updateDeliverable(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('report', $activity);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (! $request->hasFile('file') && empty($data['url']) && empty($data['caption'])) {
            throw ValidationException::withMessages([
                'caption' => 'Sube una imagen, agrega un enlace o escribe una descripción.',
            ]);
        }

        if ($activity->deliverable_path) {
            Storage::disk('local')->delete($activity->deliverable_path);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = Storage::disk('local')->putFile('deliverables', $file);

            $activity->update([
                'deliverable' => $data['caption'] ?? null,
                'deliverable_type' => 'file',
                'deliverable_path' => $path,
                'deliverable_original_name' => $file->getClientOriginalName(),
                'deliverable_mime_type' => $file->getClientMimeType(),
                'deliverable_size' => $file->getSize(),
                'deliverable_url' => null,
            ]);
        } elseif (! empty($data['url'])) {
            $activity->update([
                'deliverable' => $data['caption'] ?? null,
                'deliverable_type' => 'link',
                'deliverable_url' => $data['url'],
                'deliverable_path' => null,
                'deliverable_original_name' => null,
                'deliverable_mime_type' => null,
                'deliverable_size' => null,
            ]);
        } else {
            $activity->update([
                'deliverable' => $data['caption'],
                'deliverable_type' => 'note',
                'deliverable_url' => null,
                'deliverable_path' => null,
                'deliverable_original_name' => null,
                'deliverable_mime_type' => null,
                'deliverable_size' => null,
            ]);
        }

        return back();
    }

    public function destroyDeliverable(Activity $activity): RedirectResponse
    {
        $this->authorize('report', $activity);

        if ($activity->deliverable_path) {
            Storage::disk('local')->delete($activity->deliverable_path);
        }

        $activity->update([
            'deliverable' => null,
            'deliverable_type' => null,
            'deliverable_path' => null,
            'deliverable_original_name' => null,
            'deliverable_mime_type' => null,
            'deliverable_size' => null,
            'deliverable_url' => null,
        ]);

        return back();
    }

    public function downloadDeliverable(Activity $activity): StreamedResponse
    {
        $this->authorize('view', $activity->planGroup->monthlyPlan);

        abort_unless($activity->deliverable_path, 404);

        return Storage::disk('local')->response($activity->deliverable_path, $activity->deliverable_original_name);
    }
}
