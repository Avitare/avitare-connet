<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Company;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'Empresa']);

        $areas = [
            ['name' => 'Gerencia General', 'slug' => 'gerencia-general', 'is_gerencia' => true, 'is_marketing' => false],
            ['name' => 'Marketing', 'slug' => 'marketing', 'is_gerencia' => false, 'is_marketing' => true],
            ['name' => 'Operaciones', 'slug' => 'operaciones', 'is_gerencia' => false, 'is_marketing' => false],
            ['name' => 'Ventas', 'slug' => 'ventas', 'is_gerencia' => false, 'is_marketing' => false],
        ];

        foreach ($areas as $area) {
            Area::firstOrCreate(
                ['company_id' => $company->id, 'slug' => $area['slug']],
                [
                    'name' => $area['name'],
                    'is_gerencia' => $area['is_gerencia'],
                    'is_marketing' => $area['is_marketing'],
                    'active' => true,
                ]
            );
        }
    }
}
