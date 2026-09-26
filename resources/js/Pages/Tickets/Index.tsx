import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    SlaBadge,
    TicketPriorityBadge,
    TicketStatusBadge,
} from '@/Support/ticketDisplay';
import { TicketCategory, TicketListItem } from '@/types/models';
import { Head, Link } from '@inertiajs/react';

export default function Index({
    categories,
    tickets,
}: {
    categories: TicketCategory[];
    tickets: TicketListItem[];
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Mis tickets
                </h2>
            }
        >
            <Head title="Mis tickets" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div>
                        <h3 className="px-1 text-base font-semibold text-gray-900">
                            ¿Qué necesitas reportar?
                        </h3>
                        <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {categories.map((category) => (
                                <Link
                                    key={category.id}
                                    href={route('tickets.create', {
                                        category: category.id,
                                    })}
                                    className="flex flex-col items-center gap-2 rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm transition hover:border-green-200 hover:shadow-md"
                                >
                                    <span className="text-2xl">
                                        {category.icon ?? '🎫'}
                                    </span>
                                    <span className="text-sm font-medium text-gray-700">
                                        {category.name}
                                    </span>
                                </Link>
                            ))}
                            <Link
                                href={route('tickets.create')}
                                className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 p-4 text-center text-gray-500 transition hover:border-green-300 hover:text-green-700"
                            >
                                <span className="text-2xl">➕</span>
                                <span className="text-sm font-medium">
                                    Otra solicitud
                                </span>
                            </Link>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <h3 className="px-1 text-base font-semibold text-gray-900">
                            Mis tickets
                        </h3>

                        {tickets.length === 0 && (
                            <p className="px-1 text-sm text-gray-500">
                                Todavía no registraste ningún ticket.
                            </p>
                        )}

                        <div className="space-y-3">
                            {tickets.map((ticket) => (
                                <Link
                                    key={ticket.id}
                                    href={route('tickets.show', ticket.id)}
                                    className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md"
                                >
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span className="text-xl">
                                            {ticket.category.icon ?? '🎫'}
                                        </span>
                                        <div className="min-w-0">
                                            <div className="truncate text-sm font-medium text-gray-900">
                                                {ticket.subject}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {ticket.code} ·{' '}
                                                {ticket.category.name} ·{' '}
                                                {new Date(
                                                    ticket.created_at,
                                                ).toLocaleDateString()}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <TicketPriorityBadge
                                            priority={ticket.priority}
                                        />
                                        <SlaBadge ticket={ticket} />
                                        <TicketStatusBadge
                                            status={ticket.status}
                                        />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
