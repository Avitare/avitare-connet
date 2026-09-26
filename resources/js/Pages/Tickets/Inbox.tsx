import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    SlaBadge,
    TicketPriorityBadge,
    TicketStatusBadge,
    formatDateTime,
} from '@/Support/ticketDisplay';
import { PageProps } from '@/types';
import { Ticket, TicketCategory } from '@/types/models';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Search, UserCheck, Wrench, X } from 'lucide-react';
import { FormEventHandler, MouseEventHandler, useState } from 'react';

const TABS: { key: string; label: string }[] = [
    { key: 'abiertos', label: 'Abiertos' },
    { key: 'resueltos', label: 'Resueltos' },
    { key: 'cerrados', label: 'Cerrados / cancelados' },
    { key: 'todos', label: 'Todos' },
];

const ASSIGNED_OPTIONS: { key: string; label: string }[] = [
    { key: 'todos', label: 'Cualquier asignación' },
    { key: 'sin_asignar', label: 'Sin asignar' },
    { key: 'mios', label: 'Asignados a mí' },
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
        <form
            onSubmit={submit}
            className="mt-2 w-full space-y-2 rounded-lg bg-gray-50 p-3"
        >
            <textarea
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Describe la solución aplicada"
                value={data.solution}
                onChange={(e) => setData('solution', e.target.value)}
            />
            <InputError message={errors.solution} />
            <InputError message={(errors as Record<string, string>).ticket} />
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

function AssignInline({ ticket }: { ticket: Ticket }) {
    const { auth } = usePage<PageProps>().props;
    const isMine = ticket.assigned_to?.id === auth.user.id;

    const assignToMe: MouseEventHandler = (e) => {
        e.preventDefault();
        router.post(
            route('tickets.assign', ticket.id),
            {},
            { preserveScroll: true },
        );
    };

    if (isMine) {
        return (
            <span className="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <UserCheck className="h-3.5 w-3.5" />
                Asignado a ti
            </span>
        );
    }

    return (
        <button
            onClick={assignToMe}
            className="flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
        >
            <UserCheck className="h-3.5 w-3.5" />
            {ticket.assigned_to
                ? `Asignado a ${ticket.assigned_to.name} · Asignarme`
                : 'Asignarme'}
        </button>
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
                    <SlaBadge ticket={ticket} />
                    <TicketStatusBadge status={ticket.status} />
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                <span>Solicitante: {ticket.user.name}</span>
                {ticket.area && <span>Área: {ticket.area.name}</span>}
                <span>Creado: {formatDateTime(ticket.created_at)}</span>
            </div>

            <div
                className="mt-3 flex flex-wrap items-center gap-4"
                onClick={(e) => e.preventDefault()}
            >
                <AssignInline ticket={ticket} />
                {manageable && <ResolveInline ticketId={ticket.id} />}
            </div>
        </Link>
    );
}

export default function Inbox({
    tickets,
    statusFilter,
    categoryFilter,
    search,
    assignedFilter,
    categories,
}: {
    tickets: Ticket[];
    statusFilter: string;
    categoryFilter: number | null;
    search: string;
    assignedFilter: string;
    categories: TicketCategory[];
}) {
    const [searchValue, setSearchValue] = useState(search);

    const applyFilters = (next: {
        status?: string;
        category?: string;
        search?: string;
        assigned?: string;
    }) => {
        router.get(
            route('tickets.inbox'),
            {
                status: next.status ?? statusFilter,
                category:
                    next.category ??
                    (categoryFilter ? String(categoryFilter) : ''),
                search: next.search ?? search,
                assigned: next.assigned ?? assignedFilter,
            },
            { preserveState: true },
        );
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters({ search: searchValue });
    };

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
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-wrap gap-1 rounded-full bg-gray-100 p-1 text-sm">
                            {TABS.map((tab) => (
                                <Link
                                    key={tab.key}
                                    href={route('tickets.inbox', {
                                        status: tab.key,
                                        category: categoryFilter ?? undefined,
                                        search: search || undefined,
                                        assigned: assignedFilter,
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

                        <div className="flex flex-wrap items-center gap-2">
                            <select
                                className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                value={assignedFilter}
                                onChange={(e) =>
                                    applyFilters({ assigned: e.target.value })
                                }
                            >
                                {ASSIGNED_OPTIONS.map((option) => (
                                    <option key={option.key} value={option.key}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>

                            <select
                                className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                value={categoryFilter ?? ''}
                                onChange={(e) =>
                                    applyFilters({ category: e.target.value })
                                }
                            >
                                <option value="">Todas las categorías</option>
                                {categories.map((category) => (
                                    <option
                                        key={category.id}
                                        value={category.id}
                                    >
                                        {category.name}
                                    </option>
                                ))}
                            </select>

                            <form
                                onSubmit={submitSearch}
                                className="flex items-center gap-1"
                            >
                                <input
                                    type="text"
                                    className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                    placeholder="Buscar por código o asunto"
                                    value={searchValue}
                                    onChange={(e) =>
                                        setSearchValue(e.target.value)
                                    }
                                />
                                <button
                                    type="submit"
                                    className="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                >
                                    <Search className="h-4 w-4" />
                                </button>
                            </form>
                        </div>
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
