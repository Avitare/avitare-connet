import { Requerimiento } from '@/types/models';
import { formatNumber } from '@/Support/planDisplay';

export default function RequerimientoBudgetSummary({
    requerimiento,
}: {
    requerimiento: Requerimiento;
}) {
    return (
        <div className="mt-1 max-w-xl space-y-2">
            <ul className="space-y-1.5">
                {requerimiento.items.map((item) => (
                    <li
                        key={item.id}
                        className="rounded-lg bg-gray-50 px-3 py-2 text-sm"
                    >
                        <div className="flex items-baseline justify-between gap-2">
                            <span className="font-medium text-gray-900">
                                {item.objetivo}
                            </span>
                            <span className="shrink-0 text-gray-700">
                                {formatNumber(item.monto_solicitado)}
                            </span>
                        </div>
                        {item.fecha_requerida && (
                            <p className="text-xs text-gray-500">
                                Fecha requerida:{' '}
                                {new Date(
                                    item.fecha_requerida,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {item.especificacion_uso && (
                            <p className="mt-0.5 text-xs text-gray-500">
                                {item.especificacion_uso}
                            </p>
                        )}
                    </li>
                ))}
            </ul>
            {requerimiento.format_code && (
                <span className="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500">
                    Formato {requerimiento.format_code}
                    {requerimiento.format_version &&
                        ` · ${requerimiento.format_version}`}
                </span>
            )}
        </div>
    );
}
