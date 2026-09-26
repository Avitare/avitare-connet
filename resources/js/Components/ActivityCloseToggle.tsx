import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { router } from '@inertiajs/react';
import { Lock, Unlock } from 'lucide-react';
import { useState } from 'react';

export default function ActivityCloseToggle({
    activityId,
    closed,
    hasDeliverable,
}: {
    activityId: number;
    closed: boolean;
    hasDeliverable: boolean;
}) {
    const [processing, setProcessing] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const send = () => {
        setProcessing(true);
        router.post(
            route(
                closed ? 'activities.reopen' : 'activities.close',
                activityId,
            ),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleClick = () => {
        if (!closed && !hasDeliverable) {
            return;
        }

        setShowConfirm(true);
    };

    const confirmAction = () => {
        setShowConfirm(false);
        send();
    };

    const disabled = processing || (!closed && !hasDeliverable);

    return (
        <>
            <button
                type="button"
                onClick={handleClick}
                disabled={disabled}
                title={
                    closed
                        ? 'Reabrir actividad'
                        : hasDeliverable
                          ? 'Cerrar actividad'
                          : 'Sube un entregable antes de cerrar esta actividad'
                }
                className={
                    'rounded p-1 transition disabled:cursor-not-allowed disabled:opacity-50 ' +
                    (closed
                        ? 'text-gray-400 hover:bg-green-50 hover:text-green-700'
                        : 'text-gray-300 hover:bg-red-50 hover:text-red-600')
                }
            >
                {closed ? (
                    <Unlock className="h-3.5 w-3.5" />
                ) : (
                    <Lock className="h-3.5 w-3.5" />
                )}
            </button>

            <Modal
                show={showConfirm}
                onClose={() => setShowConfirm(false)}
                maxWidth="sm"
            >
                <div className="p-6">
                    {closed ? (
                        <>
                            <h2 className="text-lg font-medium text-gray-900">
                                ¿Reabrir esta actividad?
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Se va a volver a permitir reportar avance y
                                cambiar el entregable de esta actividad.
                            </p>
                        </>
                    ) : (
                        <>
                            <h2 className="text-lg font-medium text-gray-900">
                                ¿Estás seguro de cerrar esta actividad?
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Nadie va a poder reportar avance ni cambiar el
                                entregable mientras siga cerrada. Vas a poder
                                reabrirla más adelante desde este mismo botón si
                                lo necesitas.
                            </p>
                        </>
                    )}
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setShowConfirm(false)}>
                            Cancelar
                        </SecondaryButton>
                        {closed ? (
                            <PrimaryButton
                                onClick={confirmAction}
                                disabled={processing}
                            >
                                Reabrir actividad
                            </PrimaryButton>
                        ) : (
                            <DangerButton
                                onClick={confirmAction}
                                disabled={processing}
                            >
                                Cerrar actividad
                            </DangerButton>
                        )}
                    </div>
                </div>
            </Modal>
        </>
    );
}
