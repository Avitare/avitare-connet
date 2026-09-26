import { Requerimiento } from '@/types/models';

export default function RequerimientoMaterialsSummary({
    requerimiento,
}: {
    requerimiento: Requerimiento;
}) {
    return (
        <div className="mt-1 max-w-xl space-y-2">
            {requerimiento.needed_by && (
                <p className="text-xs text-gray-500">
                    Fecha límite requerida:{' '}
                    {new Date(requerimiento.needed_by).toLocaleDateString()}
                </p>
            )}
            <ul className="space-y-1.5">
                {requerimiento.materials.map((material) => (
                    <li
                        key={material.id}
                        className="rounded-lg bg-gray-50 px-3 py-2 text-sm"
                    >
                        <span className="font-medium text-gray-900">
                            {material.material}
                        </span>
                        {material.publico_objetivo && (
                            <span className="text-gray-500">
                                {' '}
                                — {material.publico_objetivo}
                            </span>
                        )}
                        {material.especificaciones && (
                            <p className="mt-0.5 text-xs text-gray-500">
                                {material.especificaciones}
                            </p>
                        )}
                        {material.image_url && (
                            <a
                                href={material.image_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-1 inline-block text-xs font-medium text-green-700 hover:text-green-900 hover:underline"
                            >
                                Ver imagen referencial
                            </a>
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
