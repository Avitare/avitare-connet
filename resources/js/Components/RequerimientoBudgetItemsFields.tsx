import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

export interface BudgetItemRow {
    objetivo: string;
    monto_solicitado: string;
    fecha_requerida: string;
    especificacion_uso: string;
}

export const EMPTY_BUDGET_ITEM_ROW: BudgetItemRow = {
    objetivo: '',
    monto_solicitado: '',
    fecha_requerida: '',
    especificacion_uso: '',
};

export default function RequerimientoBudgetItemsFields({
    items,
    onChange,
    errors = {},
}: {
    items: BudgetItemRow[];
    onChange: (items: BudgetItemRow[]) => void;
    errors?: Record<string, string>;
}) {
    const updateRow = (index: number, patch: Partial<BudgetItemRow>) => {
        onChange(
            items.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );
    };

    const addRow = () => {
        onChange([...items, { ...EMPTY_BUDGET_ITEM_ROW }]);
    };

    const removeRow = (index: number) => {
        if (items.length === 1) {
            return;
        }
        onChange(items.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-3">
            <InputLabel value="Solicitud para el mes" />

            {items.map((row, index) => (
                <div
                    key={index}
                    className="grid gap-3 rounded-lg border border-gray-200 p-3 sm:grid-cols-2"
                >
                    <div>
                        <label className="text-xs text-gray-500">
                            Objetivo
                        </label>
                        <TextInput
                            className="mt-1 w-full"
                            placeholder="Ej. Capacitación del equipo"
                            value={row.objetivo}
                            onChange={(e) =>
                                updateRow(index, {
                                    objetivo: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={errors[`items.${index}.objetivo`]}
                        />
                    </div>
                    <div>
                        <label className="text-xs text-gray-500">
                            Monto solicitado
                        </label>
                        <TextInput
                            type="number"
                            step="0.01"
                            className="mt-1 w-full"
                            value={row.monto_solicitado}
                            onChange={(e) =>
                                updateRow(index, {
                                    monto_solicitado: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={
                                errors[`items.${index}.monto_solicitado`]
                            }
                        />
                    </div>
                    <div>
                        <label className="text-xs text-gray-500">
                            Fecha requerida
                        </label>
                        <TextInput
                            type="date"
                            className="mt-1 w-full"
                            value={row.fecha_requerida}
                            onChange={(e) =>
                                updateRow(index, {
                                    fecha_requerida: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={errors[`items.${index}.fecha_requerida`]}
                        />
                    </div>
                    <div className="sm:col-span-2">
                        <label className="text-xs text-gray-500">
                            Especificación de uso
                        </label>
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                            rows={2}
                            value={row.especificacion_uso}
                            onChange={(e) =>
                                updateRow(index, {
                                    especificacion_uso: e.target.value,
                                })
                            }
                        />
                        <InputError
                            message={
                                errors[`items.${index}.especificacion_uso`]
                            }
                        />
                    </div>
                    {items.length > 1 && (
                        <div className="sm:col-span-2">
                            <button
                                type="button"
                                onClick={() => removeRow(index)}
                                className="text-xs font-medium text-red-600 hover:text-red-800"
                            >
                                Quitar
                            </button>
                        </div>
                    )}
                </div>
            ))}

            <InputError message={errors.items} />

            <SecondaryButton type="button" onClick={addRow}>
                + Agregar renglón
            </SecondaryButton>
        </div>
    );
}
