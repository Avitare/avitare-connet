import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { ActivityDeliverable } from '@/types/models';
import { useForm } from '@inertiajs/react';
import {
    FileText,
    Link2,
    NotebookText,
    Paperclip,
    Plus,
    Upload,
} from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type Tab = 'file' | 'link' | 'note';

function hostnameOf(url: string): string {
    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}

export default function DeliverableControl({
    activityId,
    deliverable,
    canManage,
}: {
    activityId: number;
    deliverable: ActivityDeliverable | null;
    canManage: boolean;
}) {
    const [showModal, setShowModal] = useState(false);
    const [showRemoveConfirm, setShowRemoveConfirm] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [tab, setTab] = useState<Tab>('file');

    const { data, setData, post, delete: destroy, processing, errors, reset, clearErrors } =
        useForm<{
            caption: string;
            file: File | null;
            url: string;
        }>({
            caption: '',
            file: null,
            url: '',
        });

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const pickFile = (file: File | null) => {
        setData('file', file);

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(
            file && file.type.startsWith('image/')
                ? URL.createObjectURL(file)
                : null,
        );
    };

    const openModal = () => {
        reset();
        clearErrors();
        setTab('file');
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        reset();
        clearErrors();

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(null);
    };

    const switchTab = (next: Tab) => {
        setTab(next);
        clearErrors();

        if (next !== 'file') {
            pickFile(null);
        }
        if (next !== 'link') {
            setData('url', '');
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('activities.deliverable.update', activityId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    };

    const confirmRemove = () => {
        destroy(route('activities.deliverable.destroy', activityId), {
            preserveScroll: true,
            onSuccess: () => setShowRemoveConfirm(false),
        });
    };

    const isImage =
        deliverable?.type === 'file' &&
        (deliverable.mime_type?.startsWith('image/') ?? false);
    const isPdf =
        deliverable?.type === 'file' &&
        deliverable.mime_type === 'application/pdf';
    const isNote = deliverable?.type === 'note';

    const card = deliverable && (
        <>
            {isImage ? (
                <img
                    src={deliverable.url ?? undefined}
                    alt=""
                    className="h-10 w-10 shrink-0 rounded-md border border-gray-100 object-cover"
                />
            ) : (
                <span
                    className={
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-md ' +
                        (isPdf
                            ? 'bg-red-50 text-red-500'
                            : deliverable.type === 'link'
                              ? 'bg-sky-50 text-sky-600'
                              : deliverable.type === 'note'
                                ? 'bg-amber-50 text-amber-600'
                                : 'bg-gray-100 text-gray-500')
                    }
                >
                    {isPdf ? (
                        <FileText className="h-5 w-5" />
                    ) : deliverable.type === 'link' ? (
                        <Link2 className="h-5 w-5" />
                    ) : deliverable.type === 'note' ? (
                        <NotebookText className="h-5 w-5" />
                    ) : (
                        <Paperclip className="h-5 w-5" />
                    )}
                </span>
            )}
            <div className="min-w-0">
                <div className="truncate text-xs font-medium text-gray-900">
                    {deliverable.caption ||
                        (deliverable.type === 'link'
                            ? 'Ver enlace'
                            : deliverable.file_name) ||
                        'Entregable'}
                </div>
                <div className="truncate text-[11px] text-gray-400">
                    {deliverable.type === 'link'
                        ? hostnameOf(deliverable.url ?? '')
                        : isPdf
                          ? 'PDF'
                          : isImage
                            ? 'Imagen'
                            : isNote
                              ? 'Nota'
                              : deliverable.file_name}
                </div>
            </div>
        </>
    );

    return (
        <div className="w-44">
            {deliverable ? (
                <div className="space-y-1.5">
                    {deliverable.url ? (
                        <a
                            href={deliverable.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition hover:border-green-300"
                        >
                            {card}
                        </a>
                    ) : (
                        <div className="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm">
                            {card}
                        </div>
                    )}

                    {canManage && (
                        <div className="flex items-center gap-3 px-0.5">
                            <button
                                type="button"
                                onClick={openModal}
                                className="text-xs font-medium text-green-700 hover:text-green-900"
                            >
                                Cambiar
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowRemoveConfirm(true)}
                                className="text-xs font-medium text-gray-500 hover:text-red-600"
                            >
                                Quitar
                            </button>
                        </div>
                    )}
                </div>
            ) : canManage ? (
                <SecondaryButton
                    type="button"
                    onClick={openModal}
                    className="w-full justify-center gap-1.5"
                >
                    <Plus className="h-3.5 w-3.5" />
                    Subir entregable
                </SecondaryButton>
            ) : (
                <span className="text-sm text-gray-400">—</span>
            )}

            <Modal show={showModal} onClose={closeModal} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        {deliverable ? 'Cambiar entregable' : 'Subir entregable'}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Sube una imagen o PDF, pega un enlace, o simplemente
                        escribe una descripción como entregable de esta
                        actividad.
                    </p>

                    <div className="mt-5 flex gap-2">
                        <button
                            type="button"
                            onClick={() => switchTab('file')}
                            className={
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition ' +
                                (tab === 'file'
                                    ? 'border-green-600 bg-green-50 text-green-700'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300')
                            }
                        >
                            <Paperclip className="h-4 w-4" />
                            Archivo
                        </button>
                        <button
                            type="button"
                            onClick={() => switchTab('link')}
                            className={
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition ' +
                                (tab === 'link'
                                    ? 'border-green-600 bg-green-50 text-green-700'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300')
                            }
                        >
                            <Link2 className="h-4 w-4" />
                            Enlace
                        </button>
                        <button
                            type="button"
                            onClick={() => switchTab('note')}
                            className={
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition ' +
                                (tab === 'note'
                                    ? 'border-green-600 bg-green-50 text-green-700'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300')
                            }
                        >
                            <NotebookText className="h-4 w-4" />
                            Solo descripción
                        </button>
                    </div>

                    <div className="mt-4">
                        {tab === 'file' ? (
                            <div>
                                <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 hover:border-green-400 hover:text-green-700">
                                    {previewUrl ? (
                                        <img
                                            src={previewUrl}
                                            alt=""
                                            className="h-20 w-20 rounded-lg border border-gray-200 object-cover"
                                        />
                                    ) : (
                                        <Upload className="h-6 w-6" />
                                    )}
                                    <span>
                                        {data.file
                                            ? data.file.name
                                            : 'Elegir imagen o PDF'}
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/*,.pdf"
                                        className="hidden"
                                        onChange={(e) =>
                                            pickFile(
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                </label>
                                <p className="mt-1 text-xs text-gray-400">
                                    Máx. 10MB
                                </p>
                                <InputError message={errors.file} />
                            </div>
                        ) : tab === 'link' ? (
                            <div>
                                <InputLabel value="Enlace" />
                                <TextInput
                                    type="url"
                                    className="mt-1 w-full"
                                    placeholder="https://…"
                                    value={data.url}
                                    onChange={(e) =>
                                        setData('url', e.target.value)
                                    }
                                />
                                <InputError message={errors.url} />
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">
                                Describe el entregable abajo, sin necesidad de
                                subir nada.
                            </p>
                        )}
                    </div>

                    <div className="mt-4">
                        <InputLabel
                            value={
                                tab === 'note'
                                    ? 'Descripción'
                                    : 'Descripción (opcional)'
                            }
                        />
                        <TextInput
                            className="mt-1 w-full"
                            placeholder="Ej. Informe final, brief de campaña…"
                            value={data.caption}
                            onChange={(e) =>
                                setData('caption', e.target.value)
                            }
                        />
                        <InputError message={errors.caption} />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={processing}>
                            Guardar
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal
                show={showRemoveConfirm}
                onClose={() => setShowRemoveConfirm(false)}
                maxWidth="sm"
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        ¿Quitar este entregable?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Esta acción no se puede deshacer.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            onClick={() => setShowRemoveConfirm(false)}
                        >
                            Cancelar
                        </SecondaryButton>
                        <DangerButton
                            onClick={confirmRemove}
                            disabled={processing}
                        >
                            Quitar
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
