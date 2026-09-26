import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

export interface MaterialRow {
    material: string;
    especificaciones: string;
    publico_objetivo: string;
    image: File | null;
    existingImageName?: string | null;
}

export const EMPTY_MATERIAL_ROW: MaterialRow = {
    material: '',
    especificaciones: '',
    publico_objetivo: '',
    image: null,
    existingImageName: null,
};

export default function RequerimientoMaterialsFields({
    materials,
    onChange,
    errors = {},
}: {
    materials: MaterialRow[];
    onChange: (materials: MaterialRow[]) => void;
    errors?: Record<string, string>;
}) {
    const updateRow = (index: number, patch: Partial<MaterialRow>) => {
        onChange(
            materials.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );
    };

    const addRow = () => {
        onChange([...materials, { ...EMPTY_MATERIAL_ROW }]);
    };

    const removeRow = (index: number) => {
        if (materials.length === 1) {
            return;
        }
        onChange(materials.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-3">
            <InputLabel value="Materiales requeridos" />

            {materials.map((row, index) => (
                <div
                    key={index}
                    className="grid gap-3 rounded-lg border border-gray-200 p-3 sm:grid-cols-2"
                >
                    <div>
                        <label className="text-xs text-gray-500">
                            Material requerido
                        </label>
                        <TextInput
                            className="mt-1 w-full"
                            placeholder="Ej. Banner para redes"
                            value={row.material}
                            onChange={(e) =>
                                updateRow(index, {
                                    material: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={errors[`materials.${index}.material`]}
                        />
                    </div>
                    <div>
                        <label className="text-xs text-gray-500">
                            Público objetivo (interno-externo)
                        </label>
                        <TextInput
                            className="mt-1 w-full"
                            placeholder="Ej. Clientes externos"
                            value={row.publico_objetivo}
                            onChange={(e) =>
                                updateRow(index, {
                                    publico_objetivo: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={
                                errors[`materials.${index}.publico_objetivo`]
                            }
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <label className="text-xs text-gray-500">
                            Especificaciones (objetivo, canal de difusión,
                            frase a considerar, etc)
                        </label>
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                            rows={2}
                            value={row.especificaciones}
                            onChange={(e) =>
                                updateRow(index, {
                                    especificaciones: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={
                                errors[`materials.${index}.especificaciones`]
                            }
                        />
                    </div>
                    <div className="flex items-end justify-between gap-3 sm:col-span-2">
                        <div className="flex-1">
                            <label className="text-xs text-gray-500">
                                Imagen referencial (opcional)
                            </label>
                            <input
                                type="file"
                                accept="image/*"
                                className="mt-1 block w-full text-sm text-gray-600"
                                onChange={(e) =>
                                    updateRow(index, {
                                        image: e.target.files?.[0] ?? null,
                                    })
                                }
                            />
                            {row.existingImageName && !row.image && (
                                <p className="mt-1 text-xs text-gray-400">
                                    Actual: {row.existingImageName} (elegí un
                                    archivo para reemplazarla)
                                </p>
                            )}
                            <InputError
                                message={errors[`materials.${index}.image`]}
                            />
                        </div>
                        {materials.length > 1 && (
                            <button
                                type="button"
                                onClick={() => removeRow(index)}
                                className="shrink-0 text-xs font-medium text-red-600 hover:text-red-800"
                            >
                                Quitar
                            </button>
                        )}
                    </div>
                </div>
            ))}

            <InputError message={errors.materials} />

            <SecondaryButton type="button" onClick={addRow}>
                + Agregar material
            </SecondaryButton>
        </div>
    );
}
