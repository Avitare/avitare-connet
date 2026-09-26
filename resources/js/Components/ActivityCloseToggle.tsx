import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { router } from '@inertiajs/react';
import { Lock, Unlock } from 'lucide-react';
import { useState } from 'react';

export default function ActivityCloseToggle({
    activityId,
    closed,
}: {
    activityId: number;
    closed: boolean;
}) {
    const [processing, setProcessing] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const send = () => {
        setProcessing(true);
        router.post(
            route(closed ? 'activities.reopen' : 'activities.close', activityId),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleClick = () => {
        if (closed) {
            send();
            return;
        }

        setShowConfirm(true);
    };

    const confirmClose = () => {
        setShowConfirm(false);
        send();
    };

    return (
        <>
            <button
                type="button"
                onClick={handleClick}
                disabled={processing}
                title={closed ? 'Reabrir actividad' : 'Cerrar actividad'}
                className={
                    'rounded p-1 transition disabled:opacity-50 ' +
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
                    <h2 className="text-lg font-medium text-gray-900">
                        ¿Cerrar esta actividad?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Nadie va a poder reportar avance sobre ella hasta que
                        la reabras.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            onClick={() => setShowConfirm(false)}
                        >
                            Cancelar
                        </SecondaryButton>
                        <DangerButton
                            onClick={confirmClose}
                            disabled={processing}
                        >
                            Cerrar actividad
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </>
    );
}
