import ActivityCloseToggle from '@/Components/ActivityCloseToggle';
import { ActivityStatusBadge, STATUS_HEX, complianceSeverity } from '@/Support/planDisplay';
import { Lock } from 'lucide-react';

interface GanttActivity {
    id: number;
    name: string;
    weeks: { week_number: number }[];
    compliance: number;
    status: string;
    closed?: boolean;
    can_close?: boolean;
}

export default function GanttChart({
    activities,
    totalWeeks,
    currentWeek,
}: {
    activities: GanttActivity[];
    totalWeeks: number;
    currentWeek: number | null;
}) {
    const weekLabels = Array.from({ length: totalWeeks }, (_, i) => `S${i + 1}`);

    return (
        <div className="overflow-hidden rounded-lg border border-gray-100">
            <div className="flex items-center border-b border-gray-100 bg-gray-50/80 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                <div className="w-48 shrink-0">Actividad</div>
                <div className="relative flex flex-1">
                    {weekLabels.map((label) => (
                        <div key={label} className="flex-1 text-center">
                            {label}
                        </div>
                    ))}
                    {currentWeek && (
                        <div
                            className="absolute top-0 h-full border-l-2 border-dashed border-gray-400"
                            style={{ left: `${(currentWeek / totalWeeks) * 100}%` }}
                        />
                    )}
                </div>
                <div className="ml-3 w-24 shrink-0 text-right">Estado</div>
                <div className="ml-2 w-7 shrink-0" />
            </div>

            <div className="divide-y divide-gray-50 px-4">
                {activities.map((activity, index) => {
                    const severity = complianceSeverity(activity.compliance);
                    const color = STATUS_HEX[severity];
                    const weekNumbers = activity.weeks.map((w) => w.week_number);
                    const weekStart = weekNumbers.length > 0 ? Math.min(...weekNumbers) : 1;
                    const weekEnd = weekNumbers.length > 0 ? Math.max(...weekNumbers) : 1;
                    const left = ((weekStart - 1) / totalWeeks) * 100;
                    const width = ((weekEnd - weekStart + 1) / totalWeeks) * 100;
                    const fill = Math.min(100, Math.max(0, Number(activity.compliance)));
                    const closed = activity.closed ?? false;

                    return (
                        <div
                            key={activity.id}
                            className={
                                'flex items-center gap-0 py-2 transition ' +
                                (index % 2 === 1 ? 'bg-gray-50/50' : '') +
                                (closed ? ' opacity-60' : '')
                            }
                        >
                            <div
                                className="flex w-48 shrink-0 items-center gap-1.5 truncate pr-3 text-sm text-gray-700"
                                title={activity.name}
                            >
                                {closed && (
                                    <Lock className="h-3 w-3 shrink-0 text-gray-400" />
                                )}
                                <span className="truncate">{activity.name}</span>
                            </div>
                            <div className="relative h-5 flex-1">
                                {currentWeek && (
                                    <div
                                        className="absolute top-0 z-10 h-full border-l-2 border-dashed border-gray-300"
                                        style={{ left: `${(currentWeek / totalWeeks) * 100}%` }}
                                    />
                                )}
                                <div
                                    className="absolute top-0 h-full overflow-hidden rounded"
                                    style={{
                                        left: `${left}%`,
                                        width: `${width}%`,
                                        backgroundColor: `${color}1a`,
                                    }}
                                >
                                    <div
                                        className="h-full rounded-l"
                                        style={{
                                            width: `${fill}%`,
                                            backgroundColor: color,
                                        }}
                                    />
                                </div>
                            </div>
                            <div className="ml-3 w-24 shrink-0 text-right">
                                <ActivityStatusBadge status={activity.status} />
                            </div>
                            <div className="ml-2 w-7 shrink-0 text-right">
                                {activity.can_close && (
                                    <ActivityCloseToggle
                                        activityId={activity.id}
                                        closed={closed}
                                    />
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            <div className="flex items-center gap-4 border-t border-gray-100 bg-gray-50/80 px-4 py-2 text-xs text-gray-500">
                <span className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded bg-gray-200" /> Tiempo
                    proyectado
                </span>
                <span className="flex items-center gap-1.5">
                    <span
                        className="h-2.5 w-2.5 rounded"
                        style={{ backgroundColor: STATUS_HEX.good }}
                    />{' '}
                    % completado
                </span>
                {currentWeek && (
                    <span className="flex items-center gap-1.5">
                        <span className="h-2.5 border-l-2 border-dashed border-gray-400" />{' '}
                        Semana actual
                    </span>
                )}
                <span className="ml-auto flex items-center gap-1.5">
                    <Lock className="h-3 w-3 text-gray-400" /> Cerrada
                </span>
            </div>
        </div>
    );
}
