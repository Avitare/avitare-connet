<?php

namespace Tests\Feature\Requerimientos;

use App\Models\Area;
use App\Models\Company;
use App\Models\Requerimiento;
use App\Models\RequerimientoBudgetItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PresupuestoRequerimientoTest extends TestCase
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
        $this->solicitante = User::factory()->create(['area_id' => $this->area->id]);
        $this->solicitante->assignRole('jefe_area');
    }

    private function payload(array $items): array
    {
        return [
            'type' => 'presupuesto',
            'items' => $items,
        ];
    }

    public function test_creating_a_presupuesto_requerimiento_stores_items_sums_the_amount_and_stamps_the_format(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), $this->payload([
                [
                    'objetivo' => 'Capacitación del equipo',
                    'monto_solicitado' => 1500,
                    'fecha_requerida' => '2026-10-15',
                    'especificacion_uso' => 'Curso de Laravel avanzado',
                ],
                [
                    'objetivo' => 'Licencias de software',
                    'monto_solicitado' => 800,
                ],
            ]))
            ->assertSessionHasNoErrors();

        $requerimiento = Requerimiento::where('user_id', $this->solicitante->id)->firstOrFail();

        $this->assertEquals('presupuesto', $requerimiento->type->value);
        $this->assertEquals('gerencia', $requerimiento->routed_to);
        $this->assertNull($requerimiento->detail);
        $this->assertEquals('2300.00', $requerimiento->requested_amount);
        $this->assertEquals(Requerimiento::PRESUPUESTO_FORMAT['code'], $requerimiento->format_code);
        $this->assertEquals(Requerimiento::PRESUPUESTO_FORMAT['version'], $requerimiento->format_version);
        $this->assertCount(2, $requerimiento->budgetItems);
    }

    public function test_presupuesto_requerimiento_without_items_is_rejected(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), [
                'type' => 'presupuesto',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Requerimiento::count());
    }

    public function test_correcting_an_observed_presupuesto_requerimiento_replaces_items_and_recomputes_the_amount(): void
    {
        $this->actingAs($this->solicitante)->post(route('requerimientos.store'), $this->payload([
            ['objetivo' => 'Original', 'monto_solicitado' => 1000],
        ]));

        $requerimiento = Requerimiento::firstOrFail();
        $oldItemId = $requerimiento->budgetItems->first()->id;

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');
        $this->actingAs($gerente)->post(route('requerimientos.observe', $requerimiento), ['comment' => 'Ajustar montos']);

        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.correct', $requerimiento), $this->payload([
                ['objetivo' => 'Corregido A', 'monto_solicitado' => 300],
                ['objetivo' => 'Corregido B', 'monto_solicitado' => 700],
            ]))
            ->assertSessionHasNoErrors();

        $requerimiento->refresh();

        $this->assertNull(RequerimientoBudgetItem::find($oldItemId));
        $this->assertCount(2, $requerimiento->budgetItems);
        $this->assertEquals('1000.00', $requerimiento->requested_amount);
        $this->assertEquals('corregido', $requerimiento->status->value);
    }
}
