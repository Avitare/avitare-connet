import {
    Ticket,
    TicketEventEntry,
    TicketPriority,
    TicketStatus,
} from '@/types/models';

export const TICKET_STATUS_LABEL: Record<TicketStatus, string> = {
    NUEVO: 'Nuevo',
    ASIGNADO: 'Asignado',
    EN_PROCESO: 'En proceso',
    ESPERANDO_USUARIO: 'Esperando colaborador',
    RESUELTO: 'Resuelto',
    CERRADO: 'Cerrado',
    CANCELADO: 'Cancelado',
};

const TICKET_STATUS_COLOR: Record<TicketStatus, string> = {
    NUEVO: 'bg-sky-50 text-sky-700',
    ASIGNADO: 'bg-violet-50 text-violet-700',
    EN_PROCESO: 'bg-[#fab219]/15 text-[#a06600]',
    ESPERANDO_USUARIO: 'bg-[#ec835a]/15 text-[#b34a26]',
    RESUELTO: 'bg-[#0ca30c]/10 text-[#0ca30c]',
    CERRADO: 'bg-gray-100 text-gray-600',
    CANCELADO: 'bg-[#d03b3b]/10 text-[#d03b3b]',
};

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
    return (
        <span
            className={`inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ${TICKET_STATUS_COLOR[status]}`}
        >
            {TICKET_STATUS_LABEL[status]}
        </span>
    );
}

export function TicketPriorityBadge({ priority }: { priority: TicketPriority }) {
    return (
        <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-500/20">
            <span
                className="h-2 w-2 rounded-full"
                style={{ backgroundColor: priority.color }}
            />
            {priority.name}
        </span>
    );
}

function formatMinutes(totalMinutes: number): string {
    const abs = Math.abs(totalMinutes);
    const hours = Math.floor(abs / 60);
    const minutes = abs % 60;

    return hours === 0 ? `${minutes}m` : `${hours}h ${minutes}m`;
}

export function SlaBadge({ ticket }: { ticket: Ticket }) {
    if (!ticket.sla_resolution_due_at) {
        return null;
    }

    const isTerminal = ticket.status === 'CERRADO' || ticket.status === 'CANCELADO';
    const reference = ticket.resolved_at ?? (isTerminal ? null : new Date().toISOString());

    if (!reference) {
        return null;
    }

    const dueAt = new Date(ticket.sla_resolution_due_at).getTime();
    const now = new Date(reference).getTime();
    const diffMinutes = Math.round((dueAt - now) / 60000);
    const breached = diffMinutes < 0;

    if (breached) {
        return (
            <span className="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                🔴 SLA incumplido ({formatMinutes(diffMinutes)})
            </span>
        );
    }

    const soon = diffMinutes <= 60;

    return (
        <span
            className={`inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ${
                soon ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'
            }`}
        >
            {soon ? '🟡' : '🟢'} {formatMinutes(diffMinutes)} restantes
        </span>
    );
}

const TICKET_EVENT_ICON: Record<string, string> = {
    created: '🆕',
    assigned: '👤',
    status_changed: '🔄',
    comment: '💬',
    attachment_added: '📎',
    escalated: '⬆️',
    resolved: '✅',
    confirmed: '👍',
    closed: '🔒',
    cancelled: '✖️',
};

export function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('es', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function Timeline({ events }: { events: TicketEventEntry[] }) {
    if (events.length === 0) {
        return <p className="text-sm text-gray-500">Aún no hay actividad en este ticket.</p>;
    }

    return (
        <ol className="space-y-4">
            {events.map((event) => (
                <li key={event.id} className="flex gap-3">
                    <span className="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gray-100 text-sm">
                        {TICKET_EVENT_ICON[event.type] ?? '•'}
                    </span>
                    <div className="min-w-0 flex-1 pb-1">
                        <div className="flex flex-wrap items-baseline gap-x-2">
                            <span className="text-sm font-medium text-gray-900">
                                {event.user?.name ?? 'Sistema'}
                            </span>
                            <span className="text-xs text-gray-400">
                                {formatDateTime(event.created_at)}
                            </span>
                        </div>
                        {event.body && (
                            <p className="mt-0.5 whitespace-pre-line text-sm text-gray-700">
                                {event.body}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
