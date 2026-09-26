<?php

namespace App\Enums;

enum RequerimientoStatus: string
{
    case Borrador = 'borrador';
    case Enviado = 'enviado';
    case Observado = 'observado';
    case Corregido = 'corregido';
    case Aprobado = 'aprobado';
    case Atendido = 'atendido';
    case Rechazado = 'rechazado';
    case Anulado = 'anulado';
}
