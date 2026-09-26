import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import RequerimientoBudgetSummary from '@/Components/RequerimientoBudgetSummary';
import RequerimientoMaterialsSummary from '@/Components/RequerimientoMaterialsSummary';
import RequerimientoServicioSummary from '@/Components/RequerimientoServicioSummary';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    REQUERIMIENTO_TYPE_LABEL,
    RequerimientoStatusBadge,
    formatNumber,
} from '@/Support/planDisplay';
import { Requerimiento } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface MonthOption {
    value: string;
    label: string;
}

const TABS: { key: string; label: string }[] = [
    { key: 'pendientes', label: 'Pendientes' },
    { key: 'aprobados', label: 'Aprobados' },
    { key: 'atendidos', label: 'Atendidos' },
    { key: 'rechazados', label: 'Rechazados / anulados' },
    { key: 'todos', label: 'Todos' },
];

function CommentForm({
    routeName,
    requerimientoId,
    label,
    required,
    withAmount,
}: {
    routeName: string;
    requerimientoId: number;
    label: string;
    required: boolean;
    withAmount?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        comment: '',
        approved_amount: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route(routeName, requerimientoId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button
                onClick={() => setOpen(true)}
                className="text-sm font-medium text-green-700 hover:text-green-900"
            >
                {label}
            </button>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mt-2 w-full space-y-2 rounded-lg bg-gray-50 p-3"
        >
            {withAmount && (
                <div>
                    <label className="text-xs text-gray-500">
                        Monto aprobado (opcional)
                    </label>
                    <TextInput
                        type="number"
                        step="0.01"
                        className="mt-1 w-full"
                        value={data.approved_amount}
                        onChange={(e) =>
                            setData('approved_amount', e.target.value)
                        }
                    />
                    <InputError message={errors.approved_amount} />
                </div>
            )}
            <div>
                <label className="text-xs text-gray-500">
                    Comentario {required ? '' : '(opcional)'}
                </label>
                <textarea
                    className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                    rows={2}
                    value={data.comment}
                    onChange={(e) => setData('comment', e.target.value)}
                />
                <InputError message={errors.comment} />
            </div>
            <div className="flex gap-2">
                <PrimaryButton disabled={processing}>
                    Confirmar
                </PrimaryButton>
                <SecondaryButton type="button" onClick={() => setOpen(false)}>
                    Cancelar
                </SecondaryButton>
            </div>
        </form>
    );
}

function InboxCard({ requerimiento }: { requerimiento: Requerimiento }) {
    const pending = ['enviado', 'corregido'].includes(requerimiento.status);
    const approved = requerimiento.status === 'aprobado';

    return (
        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <span className="text-xs font-medium uppercase tracking-wide text-gray-400">
                        {REQUERIMIENTO_TYPE_LABEL[requerimiento.type]}
                    </span>
                    {requerimiento.type === 'marketing' ? (
                        <RequerimientoMaterialsSummary
                            requerimiento={requerimiento}
                        />
                    ) : requerimiento.type === 'servicio' ||
                      requerimiento.type === 'ti' ? (
                        <RequerimientoServicioSummary
                            requerimiento={requerimiento}
                        />
                    ) : requerimiento.type === 'presupuesto' ? (
                        <RequerimientoBudgetSummary
                            requerimiento={requerimiento}
                        />
                    ) : (
                        <p className="mt-1 max-w-xl text-sm text-gray-900">
                            {requerimiento.detail}
                        </p>
                    )}
                </div>
                <RequerimientoStatusBadge status={requerimiento.status} />
            </div>

            <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                {requerimiento.user && (
                    <span>Solicitante: {requerimiento.user.name}</span>
                )}
                {requerimiento.area && <span>Área: {requerimiento.area.name}</span>}
                {requerimiento.requested_amount && (
                    <span>
                        Monto solicitado: {formatNumber(requerimiento.requested_amount)}
                    </span>
                )}
                {requerimiento.approved_amount && (
                    <span>
                        Monto aprobado: {formatNumber(requerimiento.approved_amount)}
                    </span>
                )}
                {requerimiento.activity && (
                    <span>Actividad: {requerimiento.activity.name}</span>
                )}
            </div>

            {pending && (
                <div className="mt-4 flex flex-wrap items-start gap-4">
                    <CommentForm
                        routeName="requerimientos.approve"
                        requerimientoId={requerimiento.id}
                        label="Aprobar"
                        required={false}
                        withAmount
                    />
                    <CommentForm
                        routeName="requerimientos.observe"
                        requerimientoId={requerimiento.id}
                        label="Observar"
                        required
                    />
                    <CommentForm
                        routeName="requerimientos.reject"
                        requerimientoId={requerimiento.id}
                        label="Rechazar"
                        required
                    />
                </div>
            )}

            {approved && (
                <div className="mt-4">
                    <CommentForm
                        routeName="requerimientos.attend"
                        requerimientoId={requerimiento.id}
                        label="Marcar como atendido"
                        required={false}
                    />
                </div>
            )}

            {requerimiento.logs.length > 0 && (
                <details className="mt-4 text-xs text-gray-500">
                    <summary className="cursor-pointer select-none font-medium text-gray-600">
                        Historial
                    </summary>
                    <ul className="mt-2 space-y-1">
                        {requerimiento.logs.map((log, i) => (
                            <li key={i}>
                                {new Date(log.created_at).toLocaleString()} —{' '}
                                {log.performed_by ?? 'Sistema'}: {log.from_status ?? '—'}{' '}
                                → {log.to_status}
                                {log.comment && (
                                    <span className="italic"> ({log.comment})</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </div>
    );
}

export default function Inbox({
    items,
    statusFilter,
    months,
    monthFilter,
}: {
    items: Requerimiento[];
    statusFilter: string;
    routedTo: string;
    months: MonthOption[];
    monthFilter: string | null;
}) {
    const onMonthChange = (value: string) => {
        router.get(
            route('requerimientos.inbox'),
            value ? { status: statusFilter, month: value } : { status: statusFilter },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Bandeja de entrada de requerimientos
                </h2>
            }
        >
            <Head title="Bandeja de requerimientos" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-wrap gap-1 rounded-full bg-gray-100 p-1 text-sm">
                            {TABS.map((tab) => (
                                <Link
                                    key={tab.key}
                                    href={route('requerimientos.inbox', {
                                        status: tab.key,
                                        ...(monthFilter ? { month: monthFilter } : {}),
                                    })}
                                    className={
                                        'rounded-full px-3.5 py-1.5 font-medium transition ' +
                                        (statusFilter === tab.key
                                            ? 'bg-white text-green-800 shadow-sm'
                                            : 'text-gray-500 hover:text-gray-800')
                                    }
                                >
                                    {tab.label}
                                </Link>
                            ))}
                        </div>

                        <div className="flex items-center gap-2">
                            <label className="text-sm text-gray-600">
                                Mes:
                            </label>
                            <select
                                className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                value={monthFilter ?? ''}
                                onChange={(e) => onMonthChange(e.target.value)}
                            >
                                <option value="">Todos los meses</option>
                                {months.map((month) => (
                                    <option key={month.value} value={month.value}>
                                        {month.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {items.length === 0 && (
                            <p className="px-1 text-sm text-gray-500">
                                No hay requerimientos en esta vista.
                            </p>
                        )}

                        {items.map((item) => (
                            <InboxCard key={item.id} requerimiento={item} />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
