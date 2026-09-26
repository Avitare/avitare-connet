<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function edit(): Response
    {
        $settings = SystemSetting::current();

        $this->authorize('view', $settings);

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = SystemSetting::current();

        $this->authorize('update', $settings);

        $data = $request->validate([
            'require_deliverable_to_close_activity' => ['required', 'boolean'],
            'require_weeks_completed_to_mark_activity_done' => ['required', 'boolean'],
            'activity_risk_threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $settings->update($data);

        return to_route('admin.settings.edit');
    }
}
