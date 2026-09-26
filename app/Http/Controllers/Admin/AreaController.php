<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAreaRequest;
use App\Http\Requests\Admin\UpdateAreaRequest;
use App\Models\Area;
use App\Models\Company;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Area::class);

        return Inertia::render('Admin/Areas/Index', [
            'areas' => Area::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Area::class);

        return Inertia::render('Admin/Areas/Create');
    }

    public function store(StoreAreaRequest $request)
    {
        Area::create([
            ...$request->validated(),
            'company_id' => Area::query()->value('company_id') ?? \App\Models\Company::firstOrCreate(['name' => 'Empresa'])->id,
        ]);

        return to_route('admin.areas.index');
    }

    public function edit(Area $area): Response
    {
        $this->authorize('update', $area);

        return Inertia::render('Admin/Areas/Edit', [
            'area' => $area,
        ]);
    }

    public function update(UpdateAreaRequest $request, Area $area)
    {
        $area->update($request->validated());

        return to_route('admin.areas.index');
    }

    public function destroy(Area $area)
    {
        $this->authorize('delete', $area);

        $area->delete();

        return to_route('admin.areas.index');
    }
}
