import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';

export default function ActivityCompletionToggle({
    activityId,
    completed,
    weeksCompleted,
}: {
    activityId: number;
    completed: boolean;
    weeksCompleted: boolean;
}) {
    const [processing, setProcessing] = useState(false);

    const toggle = () => {
        if (!completed && !weeksCompleted) {
            return;
        }

        setProcessing(true);
        router.post(
            route('activity-progress-reports.store', activityId),
            { completed: !completed },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const disabled = processing || (!completed && !weeksCompleted);

    return (
        <label
            title={
                !completed && !weeksCompleted
                    ? 'Marca todas las semanas planificadas antes de dar la actividad por cumplida'
                    : undefined
            }
            className={
                'inline-flex items-center gap-2 text-sm ' +
                (disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer')
            }
        >
            <span
                className={
                    'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition ' +
                    (completed
                        ? 'border-green-600 bg-green-600 text-white'
                        : 'border-gray-300 bg-white')
                }
            >
                {completed && <Check className="h-3.5 w-3.5" />}
            </span>
            <input
                type="checkbox"
                className="sr-only"
                checked={completed}
                disabled={disabled}
                onChange={toggle}
            />
            <span className={completed ? 'text-green-700' : 'text-gray-600'}>
                Cumplida
            </span>
        </label>
    );
}
