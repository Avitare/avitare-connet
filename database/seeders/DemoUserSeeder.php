<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $gerencia = Area::where('slug', 'gerencia-general')->firstOrFail();
        $marketing = Area::where('slug', 'marketing')->firstOrFail();
        $operaciones = Area::where('slug', 'operaciones')->firstOrFail();

        $this->makeUser('Super Admin', 'admin@ciclomensual.test', null, 'admin');
        $this->makeUser('Gerente General', 'gerencia@ciclomensual.test', $gerencia->id, 'gerencia');
        $this->makeUser('Responsable Marketing', 'marketing@ciclomensual.test', $marketing->id, 'marketing');
        $this->makeUser('Jefe de Operaciones', 'jefe.operaciones@ciclomensual.test', $operaciones->id, 'jefe_area');
    }

    private function makeUser(string $name, string $email, ?int $areaId, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'area_id' => $areaId,
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
