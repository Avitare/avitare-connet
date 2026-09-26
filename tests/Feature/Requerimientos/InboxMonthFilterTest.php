<?php

namespace Tests\Feature\Requerimientos;

use App\Models\Area;
use App\Models\Company;
use App\Models\Requerimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InboxMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerencia_sees_all_months_by_default_and_can_filter_to_one(): void
    {
        Role::findOrCreate('gerencia');
        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $solicitante = User::factory()->create(['area_id' => $area->id]);
        $solicitante->assignRole('jefe_area');

        $septiembre = Requerimiento::create([
            'area_id' => $area->id,
            'user_id' => $solicitante->id,
            'type' => 'servicio',
            'status' => 'enviado',
            'routed_to' => 'gerencia',
            'detail' => 'Requerimiento de septiembre',
            'submitted_at' => Carbon::create(2026, 9, 10),
        ]);

        $octubre = Requerimiento::create([
            'area_id' => $area->id,
            'user_id' => $solicitante->id,
            'type' => 'servicio',
            'status' => 'enviado',
            'routed_to' => 'gerencia',
            'detail' => 'Requerimiento de octubre',
            'submitted_at' => Carbon::create(2026, 10, 5),
        ]);

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');

        $responseAll = $this->actingAs($gerente)->get(route('requerimientos.inbox', ['status' => 'todos']));
        $responseAll->assertOk();
        $responseAll->assertInertia(fn ($page) => $page
            ->has('items', 2)
            ->has('months', 2)
            ->where('monthFilter', null)
        );

        $responseFiltered = $this->actingAs($gerente)->get(route('requerimientos.inbox', ['status' => 'todos', 'month' => '2026-09']));
        $responseFiltered->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.id', $septiembre->id)
            ->where('monthFilter', '2026-09')
        );

        $this->assertNotEquals($octubre->id, $septiembre->id);
    }
}
