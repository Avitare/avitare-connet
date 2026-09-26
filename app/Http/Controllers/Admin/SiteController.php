<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Site::class);

        return Inertia::render('Admin/Sites/Index', [
            'sites' => Site::orderBy('id')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Site::class);

        return Inertia::render('Admin/Sites/Create');
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        Site::create($request->validated());

        return to_route('admin.sites.index');
    }

    public function edit(Site $site): Response
    {
        $this->authorize('update', $site);

        return Inertia::render('Admin/Sites/Edit', [
            'site' => $site,
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return to_route('admin.sites.index');
    }

    public function toggle(Site $site): RedirectResponse
    {
        $this->authorize('update', $site);

        $site->update(['active' => ! $site->active]);

        return back();
    }
}
