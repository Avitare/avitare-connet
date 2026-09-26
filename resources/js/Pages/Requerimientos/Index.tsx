import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import RequerimientoBudgetItemsFields, {
    BudgetItemRow,
    EMPTY_BUDGET_ITEM_ROW,
} from '@/Components/RequerimientoBudgetItemsFields';
import RequerimientoBudgetSummary from '@/Components/RequerimientoBudgetSummary';
import RequerimientoMaterialsFields, {
    EMPTY_MATERIAL_ROW,
    MaterialRow,
} from '@/Components/RequerimientoMaterialsFields';
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
import { Requerimiento, RequerimientoRef } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

function CorrectForm({
    requerimiento,
    onDone,
}: {
    requerimiento: Requerimiento;
    onDone: () => void;
}) {
    const isMarketing = requerimiento.type === 'marketing';
    const usesGenericDetail =
        requerimiento.type === 'servicio' || requerimiento.type === 'ti';

    const { data, setData, post, processing, errors } = useForm({
        detail: requerimiento.detail ?? '',
        especificaciones: requerimiento.especificaciones ?? '',
        needed_by: requerimiento.needed_by ?? '',
        materials: (requerimiento.materials.length > 0
            ? requerimiento.materials.map(
                  (m): MaterialRow => ({
                      material: m.material,
                      especificaciones: m.especificaciones ?? '',
                      publico_objetivo: m.publico_objetivo ?? '',
                      image: null,
                      existingImageName: m.image_name,
                  }),
              )
            : [{ ...EMPTY_MATERIAL_ROW }]) as MaterialRow[],
        items: (requerimiento.items.length > 0
            ? requerimiento.items.map(
                  (i): BudgetItemRow => ({
                      objetivo: i.objetivo,
                      monto_solicitado: i.monto_solicitado,
                      fecha_requerida: i.fecha_requerida ?? '',
                      especificacion_uso: i.especificacion_uso ?? '',
                  }),
              )
            : [{ ...EMPTY_BUDGET_ITEM_ROW }]) as BudgetItemRow[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('requerimientos.correct', requerimiento.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => onDone(),
        });
    };

    return (
        <form onSubmit={submit} className="mt-3 space-y-3 rounded-lg bg-gray-50 p-4">
            {isMarketing ? (
                <>
                    <div>
                        <InputLabel value="Fecha límite requerida" />
                        <TextInput
                            type="date"
                            className="mt-1 w-full"
                            value={data.needed_by ?? ''}
                            onChange={(e) =>
                                setData('needed_by', e.target.value)
                            }
                        />
                        <InputError message={errors.needed_by} />
                    </div>
                    <RequerimientoMaterialsFields
                        materials={data.materials}
                        onChange={(materials) =>
                            setData('materials', materials)
                        }
                        errors={errors as Record<string, string>}
                    />
                </>
            ) : usesGenericDetail ? (
                <>
                    <div>
                        <InputLabel
                            value={
                                requerimiento.type === 'ti'
                                    ? 'Solicitud'
                                    : 'Detalle y/o motivo del servicio'
                            }
                        />
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                            rows={3}
                            value={data.detail}
                            onChange={(e) =>
                                setData('detail', e.target.value)
                            }
                        />
                        <InputError message={errors.detail} />
                    </div>
                    <div>
                        <InputLabel value="Especificaciones" />
                        <textarea
                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                            rows={3}
                            value={data.especificaciones}
                            onChange={(e) =>
                                setData('especificaciones', e.target.value)
                            }
                        />
                        <InputError message={errors.especificaciones} />
                    </div>
                </>
            ) : (
                <RequerimientoBudgetItemsFields
                    items={data.items}
                    onChange={(items) => setData('items', items)}
                    errors={errors as Record<string, string>}
                />
            )}
            <div className="flex gap-2">
                <PrimaryButton disabled={processing}>
                    Reenviar corrección
                </PrimaryButton>
                <SecondaryButton type="button" onClick={onDone}>
                    Cancelar
                </SecondaryButton>
            </div>
        </form>
    );
}

function RequerimientoCard({ requerimiento }: { requerimiento: Requerimiento }) {
    const [correcting, setCorrecting] = useState(false);

    const cancellable = ['borrador', 'enviado', 'observado', 'corregido'].includes(
        requerimiento.status,
    );

    const cancel = () => {
        if (!confirm('¿Anular este requerimiento?')) {
            return;
        }
        router.post(
            route('requerimientos.cancel', requerimiento.id),
            {},
            { preserveScroll: true },
        );
    };

    const lastComment = [...requerimiento.logs].reverse().find((l) => l.comment);

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
                {requerimiento.submitted_at && (
                    <span>
                        Enviado:{' '}
                        {new Date(requerimiento.submitted_at).toLocaleDateString()}
                    </span>
                )}
            </div>

            {requerimiento.status === 'observado' && lastComment && (
                <div className="mt-3 rounded-lg bg-[#ec835a]/10 p-3 text-sm text-[#b34a26]">
                    <span className="font-medium">Observación: </span>
                    {lastComment.comment}
                </div>
            )}

            {(requerimiento.status === 'rechazado' ||
                requerimiento.status === 'aprobado' ||
                requerimiento.status === 'atendido') &&
                lastComment && (
                    <div className="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                        <span className="font-medium">Comentario: </span>
                        {lastComment.comment}
                    </div>
                )}

            <div className="mt-3 flex gap-3">
                {requerimiento.status === 'observado' && !correcting && (
                    <button
                        onClick={() => setCorrecting(true)}
                        className="text-sm font-medium text-green-700 hover:text-green-900"
                    >
                        Corregir y reenviar
                    </button>
                )}
                {cancellable && (
                    <button
                        onClick={cancel}
                        className="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        Anular
                    </button>
                )}
            </div>

            {correcting && (
                <CorrectForm
                    requerimiento={requerimiento}
                    onDone={() => setCorrecting(false)}
                />
            )}
        </div>
    );
}

export default function Index({
    requerimientos,
    activities,
    canCreate,
}: {
    requerimientos: Requerimiento[];
    activities: RequerimientoRef[];
    canCreate: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'servicio',
        detail: '',
        especificaciones: '',
        activity_id: '',
        needed_by: '',
        materials: [{ ...EMPTY_MATERIAL_ROW }] as MaterialRow[],
        items: [{ ...EMPTY_BUDGET_ITEM_ROW }] as BudgetItemRow[],
    });
    const isMarketing = data.type === 'marketing';
    const usesGenericDetail = data.type === 'servicio' || data.type === 'ti';

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('requerimientos.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () =>
                reset(
                    'detail',
                    'especificaciones',
                    'activity_id',
                    'needed_by',
                    'materials',
                    'items',
                ),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Realizar requerimientos
                </h2>
            }
        >
            <Head title="Realizar requerimientos" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {canCreate && (
                        <form
                            onSubmit={submit}
                            className="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                        >
                            <h3 className="text-base font-semibold text-gray-900">
                                Nuevo requerimiento
                            </h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel value="Tipo" />
                                    <select
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                        value={data.type}
                                        onChange={(e) =>
                                            setData('type', e.target.value)
                                        }
                                    >
                                        <option value="servicio">Servicio</option>
                                        <option value="presupuesto">
                                            Presupuesto
                                        </option>
                                        <option value="marketing">Marketing</option>
                                        <option value="ti">T.I.</option>
                                    </select>
                                    <InputError message={errors.type} />
                                </div>

                                {isMarketing && (
                                    <div>
                                        <InputLabel value="Fecha límite requerida" />
                                        <TextInput
                                            type="date"
                                            className="mt-1 w-full"
                                            value={data.needed_by}
                                            onChange={(e) =>
                                                setData(
                                                    'needed_by',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.needed_by}
                                        />
                                    </div>
                                )}
                            </div>

                            {isMarketing ? (
                                <RequerimientoMaterialsFields
                                    materials={data.materials}
                                    onChange={(materials) =>
                                        setData('materials', materials)
                                    }
                                    errors={errors as Record<string, string>}
                                />
                            ) : usesGenericDetail ? (
                                <>
                                    <div>
                                        <InputLabel
                                            value={
                                                data.type === 'ti'
                                                    ? 'Solicitud'
                                                    : 'Detalle y/o motivo del servicio'
                                            }
                                        />
                                        <textarea
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                            rows={3}
                                            placeholder={
                                                data.type === 'ti'
                                                    ? 'Describe qué necesitás solicitar a T.I.…'
                                                    : 'Describe el motivo del servicio…'
                                            }
                                            value={data.detail}
                                            onChange={(e) =>
                                                setData(
                                                    'detail',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError message={errors.detail} />
                                    </div>
                                    <div>
                                        <InputLabel value="Especificaciones" />
                                        <textarea
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                            rows={3}
                                            value={data.especificaciones}
                                            onChange={(e) =>
                                                setData(
                                                    'especificaciones',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.especificaciones}
                                        />
                                    </div>
                                </>
                            ) : (
                                <RequerimientoBudgetItemsFields
                                    items={data.items}
                                    onChange={(items) =>
                                        setData('items', items)
                                    }
                                    errors={errors as Record<string, string>}
                                />
                            )}

                            {activities.length > 0 && (
                                <div>
                                    <InputLabel value="Actividad vinculada (opcional)" />
                                    <select
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                        value={data.activity_id}
                                        onChange={(e) =>
                                            setData(
                                                'activity_id',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Ninguna</option>
                                        {activities.map((activity) => (
                                            <option
                                                key={activity.id}
                                                value={activity.id}
                                            >
                                                {activity.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.activity_id} />
                                </div>
                            )}

                            <PrimaryButton disabled={processing}>
                                Enviar requerimiento
                            </PrimaryButton>
                        </form>
                    )}

                    <div className="space-y-4">
                        <h3 className="px-1 text-base font-semibold text-gray-900">
                            Mis requerimientos
                        </h3>

                        {requerimientos.length === 0 && (
                            <p className="px-1 text-sm text-gray-500">
                                Todavía no enviaste ningún requerimiento.
                            </p>
                        )}

                        {requerimientos.map((requerimiento) => (
                            <RequerimientoCard
                                key={requerimiento.id}
                                requerimiento={requerimiento}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
