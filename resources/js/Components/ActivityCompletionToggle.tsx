import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';

export default function ActivityCompletionToggle({
    activityId,
    completed,
}: {
    activityId: number;
    completed: boolean;
}) {
    const [processing, setProcessing] = useState(false);

    const toggle = () => {
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

    return (
        <label
            className={
                'inline-flex cursor-pointer items-center gap-2 text-sm ' +
                (processing ? 'opacity-60' : '')
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
                disabled={processing}
                onChange={toggle}
            />
            <span className={completed ? 'text-green-700' : 'text-gray-600'}>
                Cumplida
            </span>
        </label>
    );
}
