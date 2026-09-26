<?php

namespace App\Policies;

use App\Enums\RequerimientoStatus;
use App\Models\Requerimiento;
use App\Models\User;

class RequerimientoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Requerimiento $requerimiento): bool
    {
        if ($requerimiento->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('gerencia')) {
            return true;
        }

        return $user->hasRole('marketing') && $requerimiento->routed_to === 'marketing';
    }

    public function create(User $user): bool
    {
        return $user->area_id !== null;
    }

    /**
     * Aprobar / observar / rechazar / marcar atendido: quien recibe la bandeja
     * (Gerencia para servicio y presupuesto, Marketing para marketing).
     */
    public function decide(User $user, Requerimiento $requerimiento): bool
    {
        return match ($requerimiento->routed_to) {
            'gerencia' => $user->hasRole('gerencia'),
            'marketing' => $user->hasRole('marketing'),
            'admin' => $user->hasRole('admin'),
            default => false,
        };
    }

    public function correct(User $user, Requerimiento $requerimiento): bool
    {
        return $requerimiento->user_id === $user->id
            && $requerimiento->status === RequerimientoStatus::Observado;
    }

    public function cancel(User $user, Requerimiento $requerimiento): bool
    {
        return $requerimiento->user_id === $user->id && in_array($requerimiento->status, [
            RequerimientoStatus::Borrador,
            RequerimientoStatus::Enviado,
            RequerimientoStatus::Observado,
            RequerimientoStatus::Corregido,
        ], true);
    }
}
