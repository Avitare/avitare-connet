import { Requerimiento } from '@/types/models';

export default function RequerimientoServicioSummary({
    requerimiento,
}: {
    requerimiento: Requerimiento;
}) {
    return (
        <div className="mt-1 max-w-xl space-y-2">
            {requerimiento.user?.position && (
                <p className="text-xs text-gray-500">
                    Cargo: {requerimiento.user.position}
                </p>
            )}
            {requerimiento.detail && (
                <div>
                    <p className="text-xs font-medium text-gray-500">
                        {requerimiento.type === 'ti'
                            ? 'Solicitud'
                            : 'Detalle y/o motivo del servicio'}
                    </p>
                    <p className="text-sm text-gray-900">
                        {requerimiento.detail}
                    </p>
                </div>
            )}
            {requerimiento.especificaciones && (
                <div>
                    <p className="text-xs font-medium text-gray-500">
                        Especificaciones
                    </p>
                    <p className="text-sm text-gray-900">
                        {requerimiento.especificaciones}
                    </p>
                </div>
            )}
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
