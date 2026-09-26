<?php

namespace Tests\Feature\Requerimientos;

use App\Models\Area;
use App\Models\Company;
use App\Models\Requerimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TiRequerimientoTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');
        Role::findOrCreate('admin');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $this->solicitante = User::factory()->create(['area_id' => $this->area->id]);
        $this->solicitante->assignRole('jefe_area');
    }

    public function test_creating_a_ti_requerimiento_stores_solicitud_and_especificaciones_routed_to_admin(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), [
                'type' => 'ti',
                'detail' => 'Reporte de ventas mensual por asesor',
                'especificaciones' => 'Objetivo: medir avance; público: gerencia comercial; variables: monto vendido, unidades',
            ])
            ->assertSessionHasNoErrors();

        $requerimiento = Requerimiento::where('user_id', $this->solicitante->id)->firstOrFail();

        $this->assertEquals('ti', $requerimiento->type->value);
        $this->assertEquals('admin', $requerimiento->routed_to);
        $this->assertEquals('Reporte de ventas mensual por asesor', $requerimiento->detail);
        $this->assertEquals(Requerimiento::TI_FORMAT['code'], $requerimiento->format_code);
        $this->assertEquals(Requerimiento::TI_FORMAT['version'], $requerimiento->format_version);
    }

    public function test_admin_can_approve_a_ti_requerimiento_but_gerencia_cannot(): void
    {
        $this->actingAs($this->solicitante)->post(route('requerimientos.store'), [
            'type' => 'ti',
            'detail' => 'Acceso a nuevo sistema',
        ]);

        $requerimiento = Requerimiento::firstOrFail();

        Role::findOrCreate('gerencia');
        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');
        $this->actingAs($gerente)
            ->post(route('requerimientos.approve', $requerimiento), [])
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->post(route('requerimientos.approve', $requerimiento), [])
            ->assertSessionHasNoErrors();

        $this->assertEquals('aprobado', $requerimiento->refresh()->status->value);
    }
}
