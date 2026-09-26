import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface SystemSettings {
    require_deliverable_to_close_activity: boolean;
    require_weeks_completed_to_mark_activity_done: boolean;
    activity_risk_threshold: number;
}

function ToggleRow({
    label,
    description,
    checked,
    onChange,
}: {
    label: string;
    description: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex items-start gap-3 rounded-lg border border-gray-200 p-4 hover:border-gray-300">
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                className="mt-0.5 rounded border-gray-300"
            />
            <span>
                <span className="block text-sm font-medium text-gray-900">
                    {label}
                </span>
                <span className="mt-0.5 block text-sm text-gray-500">
                    {description}
                </span>
            </span>
        </label>
    );
}

export default function Edit({ settings }: { settings: SystemSettings }) {
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            require_deliverable_to_close_activity:
                settings.require_deliverable_to_close_activity,
            require_weeks_completed_to_mark_activity_done:
                settings.require_weeks_completed_to_mark_activity_done,
            activity_risk_threshold: String(settings.activity_risk_threshold),
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.settings.update'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Configuración del sistema
                </h2>
            }
        >
            <Head title="Configuración del sistema" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-8">
                            <div>
                                <h3 className="text-sm font-semibold uppercase text-gray-500">
                                    Actividades de planes
                                </h3>
                                <div className="mt-3 space-y-3">
                                    <ToggleRow
                                        label="Exigir entregable antes de cerrar una actividad"
                                        description="Si está activo, no se puede cerrar una actividad hasta que tenga un entregable cargado (archivo, enlace o descripción)."
                                        checked={
                                            data.require_deliverable_to_close_activity
                                        }
                                        onChange={(checked) =>
                                            setData(
                                                'require_deliverable_to_close_activity',
                                                checked,
                                            )
                                        }
                                    />
                                    <ToggleRow
                                        label="Exigir todas las semanas marcadas antes de dar por cumplida una actividad"
                                        description="Si está activo, no se puede marcar 'Cumplida' una actividad hasta que todas sus semanas planificadas estén marcadas como realizadas."
                                        checked={
                                            data.require_weeks_completed_to_mark_activity_done
                                        }
                                        onChange={(checked) =>
                                            setData(
                                                'require_weeks_completed_to_mark_activity_done',
                                                checked,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div>
                                <h3 className="text-sm font-semibold uppercase text-gray-500">
                                    Estados de actividad
                                </h3>
                                <div className="mt-3">
                                    <InputLabel
                                        htmlFor="activity_risk_threshold"
                                        value="Umbral de cumplimiento para 'En riesgo' (%)"
                                    />
                                    <TextInput
                                        id="activity_risk_threshold"
                                        type="number"
                                        min={0}
                                        max={100}
                                        className="mt-1 block w-32"
                                        value={data.activity_risk_threshold}
                                        onChange={(e) =>
                                            setData(
                                                'activity_risk_threshold',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Una actividad en curso con menos de este
                                        porcentaje de cumplimiento se marca como
                                        "En riesgo" en vez de "Al día".
                                    </p>
                                    <InputError
                                        message={errors.activity_risk_threshold}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    Guardar
                                </PrimaryButton>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-gray-600">
                                        Guardado.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
