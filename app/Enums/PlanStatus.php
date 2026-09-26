<?php

namespace App\Enums;

enum PlanStatus: string
{
    case Borrador = 'borrador';
    case Vigente = 'vigente';
    case Cerrado = 'cerrado';
}
