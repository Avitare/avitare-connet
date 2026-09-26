<?php

namespace Tests\Feature\Requerimientos;

use App\Models\Area;
use App\Models\Company;
use App\Models\Requerimiento;
use App\Models\RequerimientoMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketingRequerimientoTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Role::findOrCreate('jefe_area');
        Role::findOrCreate('marketing');
        Role::findOrCreate('gerencia');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $this->solicitante = User::factory()->create(['area_id' => $this->area->id]);
        $this->solicitante->assignRole('jefe_area');
    }

    private function payload(array $materials): array
    {
        return [
            'type' => 'marketing',
            'needed_by' => '2026-10-15',
            'materials' => $materials,
        ];
    }

    public function test_creating_a_marketing_requerimiento_stores_materials_and_format_traceability(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), $this->payload([
                [
                    'material' => 'Banner para redes',
                    'especificaciones' => 'Formato cuadrado, colores de marca',
                    'publico_objetivo' => 'Externo',
                    'image' => UploadedFile::fake()->image('banner.jpg'),
                ],
                [
                    'material' => 'Volante A5',
                    'publico_objetivo' => 'Interno',
                ],
            ]))
            ->assertSessionHasNoErrors();

        $requerimiento = Requerimiento::where('user_id', $this->solicitante->id)->firstOrFail();

        $this->assertEquals('marketing', $requerimiento->type->value);
        $this->assertEquals('marketing', $requerimiento->routed_to);
        $this->assertNull($requerimiento->detail);
        $this->assertEquals(Requerimiento::MARKETING_FORMAT['code'], $requerimiento->format_code);
        $this->assertEquals(Requerimiento::MARKETING_FORMAT['version'], $requerimiento->format_version);
        $this->assertCount(2, $requerimiento->materials);

        $withImage = $requerimiento->materials->firstWhere('material', 'Banner para redes');
        $this->assertEquals('banner.jpg', $withImage->image_original_name);
        Storage::disk('local')->assertExists($withImage->image_path);
    }

    public function test_marketing_requerimiento_without_materials_is_rejected(): void
    {
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), [
                'type' => 'marketing',
                'materials' => [],
            ])
            ->assertSessionHasErrors('materials');

        $this->assertSame(0, Requerimiento::count());
    }

    public function test_correcting_an_observed_marketing_requerimiento_replaces_the_materials(): void
    {
        $this->actingAs($this->solicitante)->post(route('requerimientos.store'), $this->payload([
            ['material' => 'Banner original'],
        ]));

        $requerimiento = Requerimiento::firstOrFail();
        $oldMaterialId = $requerimiento->materials->first()->id;

        $observer = User::factory()->create(['area_id' => $this->area->id]);
        $observer->assignRole('marketing');
        $this->actingAs($observer)->post(route('requerimientos.observe', $requerimiento), ['comment' => 'Falta detalle']);

        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.correct', $requerimiento), $this->payload([
                ['material' => 'Banner corregido'],
                ['material' => 'Segundo material'],
            ]))
            ->assertSessionHasNoErrors();

        $requerimiento->refresh();

        $this->assertNull(RequerimientoMaterial::find($oldMaterialId));
        $this->assertCount(2, $requerimiento->materials);
        $this->assertEquals('corregido', $requerimiento->status->value);
    }

    public function test_same_area_user_can_view_a_material_image_and_outsider_cannot(): void
    {
        $this->actingAs($this->solicitante)->post(route('requerimientos.store'), $this->payload([
            ['material' => 'Banner', 'image' => UploadedFile::fake()->image('banner.jpg')],
        ]));

        $material = RequerimientoMaterial::firstOrFail();

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');

        $this->actingAs($gerente)
            ->get(route('requerimiento-materials.image', $material))
            ->assertOk();

        $otherCompany = Company::create(['name' => 'Otra Empresa']);
        $otherArea = Area::create(['company_id' => $otherCompany->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $outsider = User::factory()->create(['area_id' => $otherArea->id]);

        $this->actingAs($outsider)
            ->get(route('requerimiento-materials.image', $material))
            ->assertForbidden();
    }

    public function test_servicio_requerimiento_is_unaffected_by_marketing_changes(): void
    {
        // Regresión: crear un requerimiento de marketing no debe afectar la
        // rama de servicio (ver ServicioRequerimientoTest/PresupuestoRequerimientoTest
        // para la cobertura completa de cada uno, ambos con su propio formato).
        $this->actingAs($this->solicitante)
            ->post(route('requerimientos.store'), [
                'type' => 'servicio',
                'detail' => 'Necesito una laptop nueva',
            ])
            ->assertSessionHasNoErrors();

        $requerimiento = Requerimiento::firstOrFail();

        $this->assertEquals('servicio', $requerimiento->type->value);
        $this->assertEquals('gerencia', $requerimiento->routed_to);
        $this->assertCount(0, $requerimiento->materials);
    }
}
