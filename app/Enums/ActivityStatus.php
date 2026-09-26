<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case PorIniciar = 'por_iniciar';
    case AlDia = 'al_dia';
    case EnRiesgo = 'en_riesgo';
    case Atrasada = 'atrasada';
    case Completada = 'completada';
}
