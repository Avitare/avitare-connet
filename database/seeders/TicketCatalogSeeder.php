<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Priority;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketCatalogSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Hardware', 'icon' => '💻'],
            ['name' => 'Software', 'icon' => '📦'],
            ['name' => 'Redes', 'icon' => '🌐'],
            ['name' => 'Correo', 'icon' => '📧'],
            ['name' => 'Sistemas', 'icon' => '🖥️'],
            ['name' => 'Servidores', 'icon' => '🗄️'],
            ['name' => 'Seguridad', 'icon' => '🔐'],
            ['name' => 'Telefonía', 'icon' => '☎️'],
            ['name' => 'Otros', 'icon' => '➕'],
        ])->each(fn ($data) => Category::firstOrCreate(['name' => $data['name']], $data));

        collect(['Incidente', 'Solicitud', 'Requerimiento', 'Acceso', 'Mantenimiento', 'Consulta'])
            ->each(fn ($name) => TicketType::firstOrCreate(['name' => $name]));

        collect([
            ['name' => 'Baja', 'color' => '#16A34A', 'sla_response_minutes' => 480, 'sla_resolution_minutes' => 4320, 'rank' => 1],
            ['name' => 'Media', 'color' => '#CA8A04', 'sla_response_minutes' => 120, 'sla_resolution_minutes' => 1440, 'rank' => 2],
            ['name' => 'Alta', 'color' => '#EA580C', 'sla_response_minutes' => 30, 'sla_resolution_minutes' => 480, 'rank' => 3],
            ['name' => 'Crítica', 'color' => '#DC2626', 'sla_response_minutes' => 15, 'sla_resolution_minutes' => 240, 'rank' => 4],
        ])->each(fn ($data) => Priority::firstOrCreate(['name' => $data['name']], $data));
    }
}
