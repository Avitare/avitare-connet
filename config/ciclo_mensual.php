<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umbrales de estado de actividad
    |--------------------------------------------------------------------------
    |
    | Porcentaje de cumplimiento (real / esperado, con tope 100) a partir del
    | cual una actividad cae en cada estado. Se evalúan de mayor a menor.
    |
    */
    'activity_status_thresholds' => [
        'completada' => 100,
        'al_dia' => 90,
        'en_riesgo' => 70,
        // por debajo de 'en_riesgo' => atrasada
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertas
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        // Días sin un nuevo reporte de avance antes de alertar al responsable/jefe.
        'stale_activity_days' => 7,

        // Días hábiles que un requerimiento puede estar "aprobado" sin pasar a "atendido".
        'pending_requirement_business_days' => 3,

        // Día del mes (dentro del período vigente) límite para que un plan siga en borrador
        // sin generar alerta a Gerencia.
        'plan_unapproved_deadline_day' => 7,
    ],
];
