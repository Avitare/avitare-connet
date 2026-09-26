export const MESES = [
    '',
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

export const STATUS_LABEL: Record<string, string> = {
    borrador: 'Borrador',
    vigente: 'Vigente',
    cerrado: 'Cerrado',
};

export const ACTIVITY_STATUS_LABEL: Record<string, string> = {
    por_iniciar: 'Por iniciar',
    al_dia: 'Al día',
    en_riesgo: 'En riesgo',
    atrasada: 'Atrasada',
    completada: 'Completada',
};

// Paleta de estado fija (good / warning / critical) — no se tematiza con la
// marca, se reserva para comunicar severidad de forma consistente.
export const STATUS_HEX = {
    good: '#0ca30c',
    warning: '#fab219',
    serious: '#ec835a',
    critical: '#d03b3b',
    neutral: '#898781',
} as const;

export const ACTIVITY_STATUS_COLOR: Record<string, string> = {
    por_iniciar: 'bg-gray-100 text-gray-600',
    al_dia: 'bg-[#0ca30c]/10 text-[#0ca30c]',
    en_riesgo: 'bg-[#fab219]/15 text-[#a06600]',
    atrasada: 'bg-[#d03b3b]/10 text-[#d03b3b]',
    completada: 'bg-[#0ca30c]/10 text-[#0ca30c]',
};

export function ActivityStatusBadge({ status }: { status: string }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${ACTIVITY_STATUS_COLOR[status] ?? 'bg-gray-100 text-gray-700'}`}
        >
            {ACTIVITY_STATUS_LABEL[status] ?? status}
        </span>
    );
}

export const REQUERIMIENTO_TYPE_LABEL: Record<string, string> = {
    servicio: 'Servicio',
    presupuesto: 'Presupuesto',
    marketing: 'Marketing',
    ti: 'T.I.',
};

export const REQUERIMIENTO_STATUS_LABEL: Record<string, string> = {
    borrador: 'Borrador',
    enviado: 'Enviado',
    observado: 'Observado',
    corregido: 'Corregido',
    aprobado: 'Aprobado',
    atendido: 'Atendido',
    rechazado: 'Rechazado',
    anulado: 'Anulado',
};

const REQUERIMIENTO_STATUS_COLOR: Record<string, string> = {
    borrador: 'bg-gray-100 text-gray-600',
    enviado: 'bg-[#fab219]/15 text-[#a06600]',
    observado: 'bg-[#ec835a]/15 text-[#b34a26]',
    corregido: 'bg-[#fab219]/15 text-[#a06600]',
    aprobado: 'bg-[#0ca30c]/10 text-[#0ca30c]',
    atendido: 'bg-[#0ca30c]/10 text-[#0ca30c]',
    rechazado: 'bg-[#d03b3b]/10 text-[#d03b3b]',
    anulado: 'bg-gray-100 text-gray-500',
};

export function RequerimientoStatusBadge({ status }: { status: string }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${REQUERIMIENTO_STATUS_COLOR[status] ?? 'bg-gray-100 text-gray-700'}`}
        >
            {REQUERIMIENTO_STATUS_LABEL[status] ?? status}
        </span>
    );
}

export function formatNumber(value: number | string | null): string {
    if (value === null) {
        return '—';
    }

    return Number(value).toFixed(2);
}

export function complianceSeverity(
    compliance: number | string | null,
): 'neutral' | 'good' | 'warning' | 'critical' {
    if (compliance === null) {
        return 'neutral';
    }

    const value = Number(compliance);

    if (value >= 80) {
        return 'good';
    }

    if (value >= 50) {
        return 'warning';
    }

    return 'critical';
}

const SEVERITY_LABEL: Record<string, string> = {
    neutral: 'Sin datos',
    good: 'Al día',
    warning: 'En riesgo',
    critical: 'Atrasado',
};

export function complianceSemaphore(compliance: number | string | null): {
    color: string;
    label: string;
} {
    const severity = complianceSeverity(compliance);

    return {
        color: STATUS_HEX[severity],
        label: SEVERITY_LABEL[severity],
    };
}

export function StatTile({
    label,
    value,
    hint,
    dotColor,
}: {
    label: string;
    value: string;
    hint?: string;
    dotColor?: string;
}) {
    return (
        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="text-xs font-medium tracking-wide text-gray-500 uppercase">
                {label}
            </div>
            <div className="mt-2 text-3xl font-semibold text-gray-900">
                {value}
            </div>
            {hint && (
                <div className="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                    {dotColor && (
                        <span
                            className="h-2 w-2 shrink-0 rounded-full"
                            style={{ backgroundColor: dotColor }}
                        />
                    )}
                    {hint}
                </div>
            )}
        </div>
    );
}

/**
 * Barra de progreso coloreada por severidad: el track es un gris neutro fijo
 * y el relleno toma el color de estado según el umbral (>=80 good, >=50
 * warning, si no critical) — el color nunca es la única señal, siempre va
 * acompañado del número y la etiqueta de severidad al lado.
 */
export function ComplianceMeter({
    value,
}: {
    value: number | string | null;
}) {
    const severity = complianceSeverity(value);
    const pct = value === null ? 0 : Math.min(100, Math.max(0, Number(value)));

    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
            <div
                className="h-full rounded-full transition-all"
                style={{
                    width: `${pct}%`,
                    backgroundColor: STATUS_HEX[severity],
                }}
            />
        </div>
    );
}
