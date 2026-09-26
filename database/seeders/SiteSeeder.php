<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Movilidad', 'description' => 'Gestión de vehículos y traslados del equipo.', 'url' => 'https://movilidad.grupoavitare.com/'],
            ['name' => 'Automatizador', 'description' => 'Flujos y tareas automatizadas de la operación.', 'url' => 'https://automatizador.grupoavitare.com/'],
            ['name' => 'Solti', 'description' => 'Sistema ERP de gestión empresarial.', 'url' => 'https://sistema-avitare.com/erp-login'],
            ['name' => 'Vita', 'description' => 'CRM de gestión comercial y clientes.', 'url' => 'https://vita.grupoavitare.com/login'],
            ['name' => 'Logística', 'description' => 'Seguimiento de despachos e inventario.', 'url' => 'https://logistica.grupoavitare.com/login'],
        ])->each(fn ($data) => Site::firstOrCreate(['name' => $data['name']], $data));
    }
}
