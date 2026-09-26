import ActivityCloseToggle from '@/Components/ActivityCloseToggle';
import ActivityCompletionToggle from '@/Components/ActivityCompletionToggle';
import ActivityWeeksGrid from '@/Components/ActivityWeeksGrid';
import DangerButton from '@/Components/DangerButton';
import DeliverableControl from '@/Components/DeliverableControl';
import GanttChart from '@/Components/GanttChart';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import {
    ActivityStatusBadge,
    MESES,
    STATUS_LABEL,
    formatNumber,
} from '@/Support/planDisplay';
import { PlanDetail, PlanGroupData, PlanSystemSettings } from '@/types/models';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

function AddActivityForm({
    groupId,
    totalWeeks,
    onDone,
}: {
    groupId: number;
    totalWeeks: number;
    onDone: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        responsible_name: '',
        weeks: [] as number[],
        progress_type: 'porcentaje',
        numeric_goal_target: '',
        weight: '1',
        notes: '',
    });

    const toggleWeek = (week: number) => {
        setData(
            'weeks',
            data.weeks.includes(week)
                ? data.weeks.filter((w) => w !== week)
                : [...data.weeks, week].sort((a, b) => a - b),
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('activities.store', groupId), {
            preserveScroll: true,
            onSuccess: () => onDone(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="mt-3 space-y-3 rounded-md bg-gray-50 p-4"
        >
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <TextInput
                        className="w-full"
                        placeholder="Nombre de la actividad"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} />
                </div>
                <div>
                    <TextInput
                        className="w-full"
                        placeholder="Nombre del responsable"
                        value={data.responsible_name}
                        onChange={(e) =>
                            setData('responsible_name', e.target.value)
                        }
                    />
                    <InputError message={errors.responsible_name} />
                </div>
                <div className="col-span-2">
                    <label className="text-xs text-gray-500">
                        Semanas planificadas
                    </label>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {Array.from(
                            { length: totalWeeks },
                            (_, i) => i + 1,
                        ).map((w) => (
                            <label
                                key={w}
                                className="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-xs"
                            >
                                <input
                                    type="checkbox"
                                    checked={data.weeks.includes(w)}
                                    onChange={() => toggleWeek(w)}
                                    className="rounded border-gray-300"
                                />
                                Semana {w}
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.weeks} />
                </div>
                <div>
                    <label className="text-xs text-gray-500">
                        Tipo de avance
                    </label>
                    <select
                        className="w-full rounded-md border-gray-300"
                        value={data.progress_type}
                        onChange={(e) =>
                            setData('progress_type', e.target.value)
                        }
                    >
                        <option value="porcentaje">Porcentaje</option>
                        <option value="meta_numerica">Meta numérica</option>
                    </select>
                </div>
                {data.progress_type === 'meta_numerica' && (
                    <div>
                        <label className="text-xs text-gray-500">
                            Meta (ej. 30 cotizaciones)
                        </label>
                        <TextInput
                            type="number"
                            className="w-full"
                            value={data.numeric_goal_target}
                            onChange={(e) =>
                                setData(
                                    'numeric_goal_target',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={errors.numeric_goal_target} />
                    </div>
                )}
                <div>
                    <label className="text-xs text-gray-500">Peso</label>
                    <TextInput
                        type="number"
                        step="0.01"
                        className="w-full"
                        value={data.weight}
                        onChange={(e) => setData('weight', e.target.value)}
                    />
                </div>
            </div>
            <div className="flex gap-2">
                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                <SecondaryButton type="button" onClick={onDone}>
                    Cancelar
                </SecondaryButton>
            </div>
        </form>
    );
}

function AddGroupForm({ planId }: { planId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('plan-groups.store', planId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="flex items-start gap-2">
            <div>
                <TextInput
                    className="w-64"
                    placeholder="Nombre del grupo o proyecto"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                />
                <InputError message={errors.name} />
            </div>
            <PrimaryButton disabled={processing}>Agregar grupo</PrimaryButton>
        </form>
    );
}

export default function Show({
    plan,
    compliance,
    groups,
    canManage,
    canClone,
    settings,
}: {
    plan: PlanDetail;
    compliance: number | null;
    groups: PlanGroupData[];
    canManage: boolean;
    canClone: boolean;
    settings: PlanSystemSettings;
}) {
    const { errors } = usePage().props as {
        errors?: Record<string, string>;
    };
    const [addingActivityTo, setAddingActivityTo] = useState<number | null>(
        null,
    );
    const [showCloseConfirm, setShowCloseConfirm] = useState(false);

    const doAction = (action: 'approve' | 'close' | 'clone') => {
        if (action === 'close') {
            setShowCloseConfirm(true);
            return;
        }

        router.post(route(`plans.${action}`, plan.id), {}, { preserveScroll: true });
    };

    const confirmClose = () => {
        setShowCloseConfirm(false);
        router.post(route('plans.close', plan.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {plan.area.name} — {MESES[plan.period.month]}{' '}
                        {plan.period.year}
                    </h2>
                    <span className="rounded-full bg-gray-200 px-3 py-1 text-sm text-gray-700">
                        {STATUS_LABEL[plan.status]}
                    </span>
                </div>
            }
        >
            <Head title={`Plan ${plan.area.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    {errors?.plan && (
                        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">
                            {errors.plan}
                        </div>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="text-sm text-gray-600">
                            Cumplimiento del plan:{' '}
                            <span className="font-semibold text-gray-900">
                                {formatNumber(compliance)}%
                            </span>
                        </div>

                        {canManage && (
                            <div className="flex gap-2">
                                {canClone && (
                                    <SecondaryButton
                                        onClick={() => doAction('clone')}
                                    >
                                        Clonar plan anterior
                                    </SecondaryButton>
                                )}
                                {plan.status === 'borrador' && (
                                    <PrimaryButton
                                        onClick={() => doAction('approve')}
                                    >
                                        Aprobar plan
                                    </PrimaryButton>
                                )}
                                {plan.status === 'vigente' && (
                                    <PrimaryButton
                                        onClick={() => doAction('close')}
                                    >
                                        Cerrar plan
                                    </PrimaryButton>
                                )}
                            </div>
                        )}
                    </div>

                    <Modal
                        show={showCloseConfirm}
                        onClose={() => setShowCloseConfirm(false)}
                        maxWidth="sm"
                    >
                        <div className="p-6">
                            <h2 className="text-lg font-medium text-gray-900">
                                ¿Cerrar este plan?
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Cerrar el plan lo deja de solo lectura y
                                calcula los números finales.
                            </p>
                            <div className="mt-6 flex justify-end gap-3">
                                <SecondaryButton
                                    onClick={() =>
                                        setShowCloseConfirm(false)
                                    }
                                >
                                    Cancelar
                                </SecondaryButton>
                                <DangerButton onClick={confirmClose}>
                                    Cerrar plan
                                </DangerButton>
                            </div>
                        </div>
                    </Modal>

                    {plan.status === 'cerrado' && (
                        <div className="grid grid-cols-2 gap-4 bg-white p-6 shadow-sm sm:rounded-lg md:grid-cols-4">
                            <div>
                                <div className="text-xs uppercase text-gray-500">
                                    Cumplimiento plan aprobado
                                </div>
                                <div className="text-lg font-semibold">
                                    {formatNumber(
                                        plan.final_compliance_plan_aprobado,
                                    )}
                                    %
                                </div>
                            </div>
                            <div>
                                <div className="text-xs uppercase text-gray-500">
                                    Cumplimiento total del mes
                                </div>
                                <div className="text-lg font-semibold">
                                    {formatNumber(
                                        plan.final_compliance_total_mes,
                                    )}
                                    %
                                </div>
                            </div>
                            <div>
                                <div className="text-xs uppercase text-gray-500">
                                    Efectividad
                                </div>
                                <div className="text-lg font-semibold">
                                    {formatNumber(plan.final_effectiveness)}%
                                </div>
                            </div>
                            <div>
                                <div className="text-xs uppercase text-gray-500">
                                    Puntualidad
                                </div>
                                <div className="text-lg font-semibold">
                                    {formatNumber(plan.final_punctuality)}%
                                </div>
                            </div>
                        </div>
                    )}

                    {groups.map((group) => (
                        <div
                            key={group.id}
                            className="bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-medium text-gray-900">
                                    {group.name}
                                </h3>
                                <span className="text-sm text-gray-500">
                                    Cumplimiento: {formatNumber(group.compliance)}%
                                </span>
                            </div>

                            {group.activities.length > 0 && (
                                <div className="mb-6">
                                    <GanttChart
                                        activities={group.activities}
                                        totalWeeks={plan.total_weeks}
                                        currentWeek={plan.current_week}
                                        requireDeliverableToClose={
                                            settings.require_deliverable_to_close_activity
                                        }
                                    />
                                </div>
                            )}

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Actividad
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Responsable
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Semanas
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Real / Esperado
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Cumplimiento
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Estado
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Cumplida
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Entregable
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Cerrar
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {group.activities.map((activity) => (
                                            <tr key={activity.id}>
                                                <td className="px-3 py-3 text-sm text-gray-900">
                                                    <span className="inline-flex items-center gap-1.5">
                                                        {activity.closed && (
                                                            <Lock className="h-3 w-3 shrink-0 text-gray-400" />
                                                        )}
                                                        {activity.name}
                                                    </span>
                                                    {activity.carried_over && (
                                                        <span className="ml-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">
                                                            arrastrada
                                                        </span>
                                                    )}
                                                    {activity.added_after_approval && (
                                                        <span className="ml-2 rounded bg-purple-100 px-1.5 py-0.5 text-xs text-purple-700">
                                                            agregada en el mes
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-sm text-gray-500">
                                                    {activity.responsible_name}
                                                </td>
                                                <td className="px-3 py-3 text-sm text-gray-500">
                                                    <ActivityWeeksGrid
                                                        weeks={activity.weeks}
                                                        totalWeeks={
                                                            plan.total_weeks
                                                        }
                                                        canToggle={
                                                            activity.can_report
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-3 text-sm text-gray-500">
                                                    {formatNumber(
                                                        activity.real,
                                                    )}{' '}
                                                    /{' '}
                                                    {formatNumber(
                                                        activity.expected,
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-sm text-gray-500">
                                                    {formatNumber(
                                                        activity.compliance,
                                                    )}
                                                    %
                                                </td>
                                                <td className="px-3 py-3 text-sm">
                                                    <ActivityStatusBadge
                                                        status={
                                                            activity.status
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-3 text-sm">
                                                    {activity.can_report ? (
                                                        <ActivityCompletionToggle
                                                            activityId={
                                                                activity.id
                                                            }
                                                            completed={
                                                                activity.completed
                                                            }
                                                            weeksCompleted={
                                                                !settings.require_weeks_completed_to_mark_activity_done ||
                                                                activity.weeks.every(
                                                                    (w) =>
                                                                        w.completed_at !==
                                                                        null,
                                                                )
                                                            }
                                                        />
                                                    ) : (
                                                        <span
                                                            className={
                                                                activity.completed
                                                                    ? 'text-sm text-green-700'
                                                                    : 'text-sm text-gray-400'
                                                            }
                                                        >
                                                            {activity.completed
                                                                ? 'Cumplida'
                                                                : '—'}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-sm text-gray-500">
                                                    <DeliverableControl
                                                        activityId={
                                                            activity.id
                                                        }
                                                        deliverable={
                                                            activity.deliverable
                                                        }
                                                        canManage={
                                                            activity.can_report
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-3 text-sm">
                                                    {activity.can_close && (
                                                        <ActivityCloseToggle
                                                            activityId={
                                                                activity.id
                                                            }
                                                            closed={
                                                                activity.closed
                                                            }
                                                            hasDeliverable={
                                                                !settings.require_deliverable_to_close_activity ||
                                                                activity.deliverable !==
                                                                    null
                                                            }
                                                        />
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {group.activities.length === 0 && (
                                            <tr>
                                                <td
                                                    className="px-3 py-3 text-sm text-gray-500"
                                                    colSpan={9}
                                                >
                                                    Sin actividades todavía.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {canManage &&
                                (addingActivityTo === group.id ? (
                                    <AddActivityForm
                                        groupId={group.id}
                                        totalWeeks={plan.total_weeks}
                                        onDone={() =>
                                            setAddingActivityTo(null)
                                        }
                                    />
                                ) : (
                                    <button
                                        onClick={() =>
                                            setAddingActivityTo(group.id)
                                        }
                                        className="mt-3 text-sm text-indigo-600 hover:text-indigo-900"
                                    >
                                        + Agregar actividad
                                    </button>
                                ))}
                        </div>
                    ))}

                    {canManage && (
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <AddGroupForm planId={plan.id} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
