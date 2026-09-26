import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    SlaBadge,
    TicketPriorityBadge,
    TicketStatusBadge,
    Timeline,
    formatDateTime,
} from '@/Support/ticketDisplay';
import { PageProps } from '@/types';
import { Ticket } from '@/types/models';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    AlignLeft,
    CheckCircle2,
    Info,
    MessageSquare,
    Paperclip,
    RefreshCw,
    Send,
    Star,
    Wrench,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

function CommentForm({ ticketId }: { ticketId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.comment', ticketId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            <textarea
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Escribe un comentario…"
                value={data.body}
                onChange={(e) => setData('body', e.target.value)}
            />
            <InputError message={errors.body} />
            <PrimaryButton disabled={processing} className="gap-1.5">
                <Send className="h-3.5 w-3.5" />
                Comentar
            </PrimaryButton>
        </form>
    );
}

function AttachmentForm({ ticketId }: { ticketId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.attachment', ticketId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="flex items-center gap-2">
            <input
                type="file"
                className="text-sm"
                onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
            />
            <SecondaryButton
                disabled={processing || !data.file}
                className="gap-1.5"
            >
                <Paperclip className="h-3.5 w-3.5" />
                Adjuntar
            </SecondaryButton>
            <InputError message={errors.file} />
        </form>
    );
}

function ConfirmBox({ ticketId }: { ticketId: number }) {
    const confirmTicket = () => {
        router.post(
            route('tickets.confirm', ticketId),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="rounded-xl border border-[#0ca30c]/20 bg-[#0ca30c]/5 p-4">
            <p className="text-sm text-gray-700">
                El técnico marcó este ticket como resuelto. Si el problema
                quedó solucionado, confirma para cerrarlo.
            </p>
            <button
                onClick={confirmTicket}
                className="mt-3 inline-flex items-center gap-1.5 rounded-md bg-[#0ca30c] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:brightness-95"
            >
                <CheckCircle2 className="h-4 w-4" />
                Confirmar solución y cerrar ticket
            </button>
        </div>
    );
}

function RateBox({ ticketId }: { ticketId: number }) {
    const [rating, setRating] = useState(0);
    const { data, setData, post, processing } = useForm({
        rating: 0,
        comment: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.rate', ticketId), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <p className="flex items-center gap-1.5 text-sm font-medium text-gray-900">
                <Star className="h-4 w-4 text-amber-400" />
                ¿Cómo calificarías la atención recibida?
            </p>
            <div className="mt-2 flex gap-1 text-2xl">
                {[1, 2, 3, 4, 5].map((star) => (
                    <button
                        type="button"
                        key={star}
                        onClick={() => {
                            setRating(star);
                            setData('rating', star);
                        }}
                        className={
                            star <= rating ? 'text-amber-400' : 'text-gray-300'
                        }
                    >
                        ★
                    </button>
                ))}
            </div>
            <textarea
                className="mt-2 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Comentario (opcional)"
                value={data.comment}
                onChange={(e) => setData('comment', e.target.value)}
            />
            <PrimaryButton
                className="mt-2 gap-1.5"
                disabled={processing || rating === 0}
            >
                <Star className="h-3.5 w-3.5" />
                Calificar
            </PrimaryButton>
        </form>
    );
}

function StatusActions({ ticket }: { ticket: Ticket }) {
    const { data, setData, post, processing, errors } = useForm({
        status: '',
        note: '',
    });

    const options = [
        { value: 'EN_PROCESO', label: 'En proceso' },
        { value: 'ESPERANDO_USUARIO', label: 'Esperando colaborador' },
        { value: 'CANCELADO', label: 'Cancelar' },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.status', ticket.id), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-2 rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <p className="flex items-center gap-1.5 text-sm font-medium text-gray-900">
                <RefreshCw className="h-4 w-4" />
                Cambiar estado
            </p>
            <select
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                value={data.status}
                onChange={(e) => setData('status', e.target.value)}
            >
                <option value="">Selecciona…</option>
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
            <textarea
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Nota (opcional)"
                value={data.note}
                onChange={(e) => setData('note', e.target.value)}
            />
            <InputError message={errors.status} />
            <InputError
                message={(errors as Record<string, string>).ticket}
            />
            <SecondaryButton
                disabled={processing || !data.status}
                className="gap-1.5"
            >
                <RefreshCw className="h-3.5 w-3.5" />
                Actualizar estado
            </SecondaryButton>
        </form>
    );
}

function ResolveBox({ ticket }: { ticket: Ticket }) {
    const { data, setData, post, processing, errors } = useForm({
        solution: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.resolve', ticket.id), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-2 rounded-xl border border-[#0ca30c]/20 bg-[#0ca30c]/5 p-4"
        >
            <p className="flex items-center gap-1.5 text-sm font-medium text-gray-900">
                <Wrench className="h-4 w-4" />
                Marcar como resuelto
            </p>
            <textarea
                className="w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                rows={2}
                placeholder="Describe la solución aplicada"
                value={data.solution}
                onChange={(e) => setData('solution', e.target.value)}
            />
            <InputError message={errors.solution} />
            <PrimaryButton
                disabled={processing || !data.solution}
                className="gap-1.5"
            >
                <Wrench className="h-3.5 w-3.5" />
                Resolver ticket
            </PrimaryButton>
        </form>
    );
}

export default function Show({
    ticket,
    can,
}: {
    ticket: Ticket;
    can: { manage: boolean; confirm: boolean };
}) {
    const { auth } = usePage<PageProps>().props;
    const isOwner = auth.user.id === ticket.user.id;
    const canComment = !['CERRADO', 'CANCELADO'].includes(ticket.status);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wide text-gray-400">
                            {ticket.code}
                        </span>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {ticket.subject}
                        </h2>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <TicketStatusBadge status={ticket.status} />
                        <TicketPriorityBadge priority={ticket.priority} />
                        <SlaBadge ticket={ticket} />
                    </div>
                </div>
            }
        >
            <Head title={ticket.subject} />

            <div className="py-12">
                <div className="mx-auto grid max-w-6xl gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                    <div className="space-y-4 lg:col-span-2">
                        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <h3 className="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                                <AlignLeft className="h-4 w-4 text-gray-400" />
                                Descripción
                            </h3>
                            <p className="mt-2 whitespace-pre-line text-sm text-gray-700">
                                {ticket.description}
                            </p>
                        </div>

                        {can.confirm && ticket.status === 'RESUELTO' && (
                            <ConfirmBox ticketId={ticket.id} />
                        )}

                        {isOwner &&
                            ticket.status === 'CERRADO' &&
                            !ticket.satisfaction_rating && (
                                <RateBox ticketId={ticket.id} />
                            )}

                        {can.manage && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <StatusActions ticket={ticket} />
                                <ResolveBox ticket={ticket} />
                            </div>
                        )}

                        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <h3 className="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                                <MessageSquare className="h-4 w-4 text-gray-400" />
                                Actividad
                            </h3>
                            <div className="mt-4">
                                <Timeline events={ticket.events} />
                            </div>

                            {canComment && (
                                <div className="mt-4 space-y-3 border-t border-gray-100 pt-4">
                                    <CommentForm ticketId={ticket.id} />
                                    <AttachmentForm ticketId={ticket.id} />
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <h3 className="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                                <Info className="h-4 w-4 text-gray-400" />
                                Detalles
                            </h3>
                            <dl className="mt-3 space-y-2 text-sm">
                                <div className="flex justify-between gap-2">
                                    <dt className="text-gray-500">
                                        Solicitante
                                    </dt>
                                    <dd className="text-right text-gray-900">
                                        {ticket.user.name}
                                    </dd>
                                </div>
                                {ticket.area && (
                                    <div className="flex justify-between gap-2">
                                        <dt className="text-gray-500">Área</dt>
                                        <dd className="text-right text-gray-900">
                                            {ticket.area.name}
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between gap-2">
                                    <dt className="text-gray-500">
                                        Categoría
                                    </dt>
                                    <dd className="text-right text-gray-900">
                                        {ticket.category.name}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt className="text-gray-500">Tipo</dt>
                                    <dd className="text-right text-gray-900">
                                        {ticket.type.name}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-2">
                                    <dt className="text-gray-500">Creado</dt>
                                    <dd className="text-right text-gray-900">
                                        {formatDateTime(ticket.created_at)}
                                    </dd>
                                </div>
                                {ticket.resolved_at && (
                                    <div className="flex justify-between gap-2">
                                        <dt className="text-gray-500">
                                            Resuelto
                                        </dt>
                                        <dd className="text-right text-gray-900">
                                            {formatDateTime(ticket.resolved_at)}
                                        </dd>
                                    </div>
                                )}
                                {ticket.satisfaction_rating && (
                                    <div className="flex justify-between gap-2">
                                        <dt className="text-gray-500">
                                            Calificación
                                        </dt>
                                        <dd className="text-right text-amber-500">
                                            {'★'.repeat(
                                                ticket.satisfaction_rating,
                                            )}
                                            {'☆'.repeat(
                                                5 -
                                                    ticket.satisfaction_rating,
                                            )}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        {ticket.attachments.length > 0 && (
                            <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                                <h3 className="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                                    <Paperclip className="h-4 w-4 text-gray-400" />
                                    Adjuntos
                                </h3>
                                <ul className="mt-3 space-y-2 text-sm">
                                    {ticket.attachments.map((attachment) => (
                                        <li key={attachment.id}>
                                            <a
                                                href={attachment.download_url}
                                                className="flex items-center gap-1.5 text-green-700 hover:text-green-900"
                                            >
                                                <Paperclip className="h-3.5 w-3.5" />
                                                {attachment.original_name}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
