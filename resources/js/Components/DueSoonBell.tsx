import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';

interface DueSoonActivity {
    id: number;
    name: string;
    responsible_name: string;
    due_week: number;
}

export default function DueSoonBell({
    activities,
    currentWeek,
    planId,
}: {
    activities: DueSoonActivity[];
    currentWeek: number;
    planId: number;
}) {
    const count = activities.length;

    return (
        <Popover className="relative">
            <PopoverButton className="relative flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition hover:bg-white/25 focus:outline-none">
                <Bell className="h-5 w-5" />
                {count > 0 && (
                    <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                        {count}
                    </span>
                )}
            </PopoverButton>

            <PopoverPanel
                anchor="bottom end"
                className="z-30 mt-2 w-80 rounded-lg border border-gray-100 bg-white p-3 text-left shadow-lg"
            >
                <h4 className="px-1 text-sm font-medium text-gray-900">
                    Actividades por vencer
                </h4>
                <p className="px-1 text-xs text-gray-400">
                    Terminan esta semana o la próxima
                </p>

                {count === 0 ? (
                    <p className="mt-3 px-1 text-sm text-gray-500">
                        No hay actividades por vencer.
                    </p>
                ) : (
                    <ul className="mt-2 max-h-72 divide-y divide-gray-100 overflow-y-auto">
                        {activities.map((activity) => (
                            <li key={activity.id} className="px-1 py-2">
                                <Link
                                    href={route('plans.show', planId)}
                                    className="block text-sm font-medium text-gray-900 hover:text-green-700"
                                >
                                    {activity.name}
                                </Link>
                                <p className="text-xs text-gray-500">
                                    {activity.responsible_name} · vence{' '}
                                    {activity.due_week === currentWeek
                                        ? 'esta semana'
                                        : `la semana ${activity.due_week}`}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </PopoverPanel>
        </Popover>
    );
}
