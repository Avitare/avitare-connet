<?php

namespace Tests\Feature\Requerimientos;

use App\Models\Area;
use App\Models\Company;
use App\Models\Requerimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServicioRequerimientoTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');
        Role::findOrCreate('gerencia');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $this->solicitante = User::factory()->create(['area_id' => $this->area->id, 'position' => 'Coordinador de TI']);
        $this->solicitante->assignRole('jefe_area');
    }

    public function test_creating_a_servicio_requerimiento_stores_detail_and_especificaciones_with_format_traceability(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), [
                'type' => 'servicio',
                'detail' => 'Necesito una laptop nueva',
                'especificaciones' => 'Core i7, 16GB RAM, para diseño gráfico',
            ])
            ->assertSessionHasNoErrors();

        $requerimiento = Requerimiento::where('user_id', $this->solicitante->id)->firstOrFail();

        $this->assertEquals('servicio', $requerimiento->type->value);
        $this->assertEquals('gerencia', $requerimiento->routed_to);
        $this->assertEquals('Necesito una laptop nueva', $requerimiento->detail);
        $this->assertEquals('Core i7, 16GB RAM, para diseño gráfico', $requerimiento->especificaciones);
        $this->assertNull($requerimiento->requested_amount);
        $this->assertEquals(Requerimiento::SERVICIO_FORMAT['code'], $requerimiento->format_code);
        $this->assertEquals(Requerimiento::SERVICIO_FORMAT['version'], $requerimiento->format_version);
        $this->assertEquals('Coordinador de TI', $requerimiento->user->position);
    }

    public function test_correcting_an_observed_servicio_requerimiento_updates_detail_and_especificaciones(): void
    {
        $this->actingAs($this->solicitante)->post(route('requerimientos.store'), [
            'type' => 'servicio',
            'detail' => 'Detalle original',
        ]);

        $requerimiento = Requerimiento::firstOrFail();

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');
        $this->actingAs($gerente)->post(route('requerimientos.observe', $requerimiento), ['comment' => 'Falta info']);

        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.correct', $requerimiento), [
                'detail' => 'Detalle corregido',
                'especificaciones' => 'Especificaciones agregadas',
            ])
            ->assertSessionHasNoErrors();

        $requerimiento->refresh();

        $this->assertEquals('Detalle corregido', $requerimiento->detail);
        $this->assertEquals('Especificaciones agregadas', $requerimiento->especificaciones);
        $this->assertEquals('corregido', $requerimiento->status->value);
    }

    // Presupuesto ahora tiene su propio formato de renglones — ver
    // PresupuestoRequerimientoTest.php para su cobertura completa.
}
