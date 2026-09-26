import { ActivityWeekData } from '@/types/models';
import { router } from '@inertiajs/react';

export default function ActivityWeeksGrid({
    weeks,
    totalWeeks,
    canToggle,
}: {
    weeks: ActivityWeekData[];
    totalWeeks: number;
    canToggle: boolean;
}) {
    const byWeekNumber = new Map(weeks.map((w) => [w.week_number, w]));

    const toggle = (week: ActivityWeekData) => {
        if (!canToggle) {
            return;
        }

        router.patch(
            route('activity-weeks.toggle', week.id),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex gap-1">
            {Array.from({ length: totalWeeks }, (_, i) => i + 1).map(
                (weekNumber) => {
                    const week = byWeekNumber.get(weekNumber);
                    const planned = week !== undefined;
                    const completed = planned && week.completed_at !== null;

                    return (
                        <button
                            key={weekNumber}
                            type="button"
                            disabled={!planned || !canToggle}
                            onClick={() => week && toggle(week)}
                            title={
                                planned
                                    ? `Semana ${weekNumber}${completed ? ' — realizada' : ' — planificada'}`
                                    : `Semana ${weekNumber} — no planificada`
                            }
                            className={
                                'flex h-6 w-6 items-center justify-center rounded text-[10px] font-medium ' +
                                (completed
                                    ? 'bg-green-600 text-white'
                                    : planned
                                      ? 'border border-gray-300 bg-white text-gray-500' +
                                        (canToggle
                                            ? ' cursor-pointer hover:bg-gray-50'
                                            : '')
                                      : 'bg-gray-50 text-gray-300')
                            }
                        >
                            {weekNumber}
                        </button>
                    );
                },
            )}
        </div>
    );
}
