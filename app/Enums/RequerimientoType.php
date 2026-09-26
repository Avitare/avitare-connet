<?php

namespace App\Enums;

enum RequerimientoType: string
{
    case Servicio = 'servicio';
    case Presupuesto = 'presupuesto';
    case Marketing = 'marketing';
    case Ti = 'ti';

    /**
     * Ruteo por defecto (spec sección 3): servicio y presupuesto → Gerencia; marketing → Marketing; T.I. → Admin (TI).
     */
    public function routedTo(): string
    {
        return match ($this) {
            self::Marketing => 'marketing',
            self::Ti => 'admin',
            default => 'gerencia',
        };
    }
}
