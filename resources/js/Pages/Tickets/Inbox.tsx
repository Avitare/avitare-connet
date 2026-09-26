import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    TicketPriorityBadge,
    TicketStatusBadge,
    formatDateTime,
} from '@/Support/ticketDisplay';
import { Ticket } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Wrench, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const TABS: { key: string; label: string }[] = [
    { key: 'abiertos', label: 'Abiertos' },
    { key: 'resueltos', label: 'Resueltos' },
    { key: 'cerrados', label: 'Cerrados / cancelados' },
    { key: 'todos', label: 'Todos' },
];

function ResolveInline({ ticketId }: { ticketId: number }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        solution: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.resolve', ticketId), {
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
                className="flex items-center gap-1.5 text-sm font-medium text-green-700 hover:text-green-900"
            >
                <Wrench className="h-3.5 w-3.5" />
                Resolver
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="mt-2 w-full space-y-2 rounded-lg bg-gray-50 p-3">
            <textarea
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Describe la solución aplicada"
                value={data.solution}
                onChange={(e) => setData('solution', e.target.value)}
            />
            <InputError message={errors.solution} />
            <div className="flex gap-2">
                <PrimaryButton
                    disabled={processing || !data.solution}
                    className="gap-1.5"
                >
                    <CheckCircle2 className="h-3.5 w-3.5" />
                    Confirmar
                </PrimaryButton>
                <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
                >
                    <X className="h-3.5 w-3.5" />
                    Cancelar
                </button>
            </div>
        </form>
    );
}

function InboxCard({ ticket }: { ticket: Ticket }) {
    const manageable = ['NUEVO', 'EN_PROCESO', 'ESPERANDO_USUARIO'].includes(
        ticket.status,
    );

    return (
        <Link
            href={route('tickets.show', ticket.id)}
            className="block rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md"
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <span className="text-xs font-medium uppercase tracking-wide text-gray-400">
                        {ticket.code} · {ticket.category.name}
                    </span>
                    <p className="mt-1 max-w-xl text-sm font-medium text-gray-900">
                        {ticket.subject}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <TicketPriorityBadge priority={ticket.priority} />
                    <TicketStatusBadge status={ticket.status} />
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                <span>Solicitante: {ticket.user.name}</span>
                {ticket.area && <span>Área: {ticket.area.name}</span>}
                <span>Creado: {formatDateTime(ticket.created_at)}</span>
            </div>

            {manageable && (
                <div
                    className="mt-3"
                    onClick={(e) => e.preventDefault()}
                >
                    <ResolveInline ticketId={ticket.id} />
                </div>
            )}
        </Link>
    );
}

export default function Inbox({
    tickets,
    statusFilter,
}: {
    tickets: Ticket[];
    statusFilter: string;
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Bandeja de tickets
                </h2>
            }
        >
            <Head title="Bandeja de tickets" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-1 rounded-full bg-gray-100 p-1 text-sm">
                        {TABS.map((tab) => (
                            <Link
                                key={tab.key}
                                href={route('tickets.inbox', {
                                    status: tab.key,
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

                    <div className="space-y-4">
                        {tickets.length === 0 && (
                            <p className="px-1 text-sm text-gray-500">
                                No hay tickets en esta vista.
                            </p>
                        )}

                        {tickets.map((ticket) => (
                            <InboxCard key={ticket.id} ticket={ticket} />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
