import DueSoonBell from '@/Components/DueSoonBell';
import GanttChart from '@/Components/GanttChart';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ActivityStatusBadge,
    ComplianceMeter,
    MESES,
    STATUS_HEX,
    STATUS_LABEL,
    StatTile,
    complianceSemaphore,
    complianceSeverity,
    formatNumber,
} from '@/Support/planDisplay';
import { PageProps, Role } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

const roleLabels: Record<Role, string> = {
    admin: 'Super Admin',
    gerencia: 'Gerencia General',
    jefe_area: 'Jefe de área',
    marketing: 'Marketing',
};

interface AreaOverviewRow {
    area: { id: number; name: string };
    plan_id: number | null;
    status: string | null;
    compliance: number | null;
    weekly: Record<string, number | null> | null;
}

function WeeklyStrip({ weekly }: { weekly: Record<string, number | null> | null }) {
    if (!weekly) {
        return null;
    }

    const weeks = Object.keys(weekly).sort((a, b) => Number(a) - Number(b));

    return (
        <div
            className="mt-3 grid gap-1"
            style={{ gridTemplateColumns: `repeat(${weeks.length}, minmax(0, 1fr))` }}
        >
            {weeks.map((week) => {
                const value = weekly[week];
                const severity = complianceSeverity(value);

                return (
                    <div
                        key={week}
                        className="rounded px-1 py-1 text-center"
                        style={{
                            backgroundColor:
                                value === null ? '#f3f4f6' : `${STATUS_HEX[severity]}1a`,
                        }}
                        title={`Semana ${week}: ${formatNumber(value)}%`}
                    >
                        <div className="text-[10px] uppercase text-gray-400">
                            S{week}
                        </div>
                        <div className="text-xs font-medium text-gray-700">
                            {value === null ? '—' : `${Math.round(Number(value))}%`}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

interface PeriodOption {
    id: number;
    year: number;
    month: number;
}

interface AttentionActivity {
    id: number;
    name: string;
    responsible_name: string;
    status: string;
}

interface MyPlanGroupActivity {
    id: number;
    name: string;
    weeks: { week_number: number }[];
    compliance: number;
    status: string;
    closed: boolean;
    can_close: boolean;
}

interface MyPlanGroup {
    id: number;
    name: string;
    compliance: number | null;
    activities: MyPlanGroupActivity[];
}

interface DueSoonActivity {
    id: number;
    name: string;
    responsible_name: string;
    due_week: number;
}

interface MyPlan {
    plan_id: number;
    status: string;
    compliance: number | null;
    compliance_is_total: boolean;
    activities_total: number;
    activities_completed: number;
    total_weeks: number;
    current_week: number | null;
    groups: MyPlanGroup[];
    attention: AttentionActivity[];
    due_soon: DueSoonActivity[];
}

interface DashboardProps {
    period: { id: number; year: number; month: number } | null;
    areasOverview?: AreaOverviewRow[];
    periods?: PeriodOption[];
    myPlan?: MyPlan | null;
}

function AreasOverview({ areasOverview }: { areasOverview: AreaOverviewRow[] }) {
    const total = areasOverview.length;
    const withCompliance = areasOverview.filter((r) => r.compliance !== null);
    const avgCompliance =
        withCompliance.length > 0
            ? withCompliance.reduce((sum, r) => sum + Number(r.compliance), 0) /
              withCompliance.length
            : null;
    const alDia = areasOverview.filter(
        (r) => r.compliance !== null && Number(r.compliance) >= 80,
    ).length;
    const sinAprobar = areasOverview.filter((r) => r.status === 'borrador').length;

    return (
        <>
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile label="Áreas activas" value={String(total)} />
                <StatTile
                    label="Cumplimiento promedio"
                    value={
                        avgCompliance === null
                            ? '—'
                            : `${avgCompliance.toFixed(0)}%`
                    }
                    hint={
                        avgCompliance === null
                            ? 'Todavía sin datos'
                            : complianceSemaphore(avgCompliance).label
                    }
                    dotColor={
                        avgCompliance === null
                            ? undefined
                            : complianceSemaphore(avgCompliance).color
                    }
                />
                <StatTile
                    label="Áreas al día"
                    value={`${alDia}/${total}`}
                    hint="Cumplimiento ≥ 80%"
                    dotColor="#0ca30c"
                />
                <StatTile
                    label="Planes sin aprobar"
                    value={String(sinAprobar)}
                    hint={sinAprobar > 0 ? 'Todavía en borrador' : 'Todo aprobado'}
                    dotColor={sinAprobar > 0 ? '#fab219' : '#0ca30c'}
                />
            </div>

            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {areasOverview.map((row) => {
                    const semaphore = complianceSemaphore(row.compliance);

                    return (
                        <div
                            key={row.area.id}
                            className="flex flex-col rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <h4 className="font-medium text-gray-900">
                                    {row.area.name}
                                </h4>
                                <span className="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {row.status
                                        ? STATUS_LABEL[row.status]
                                        : 'Sin plan'}
                                </span>
                            </div>

                            <div className="mt-4 text-2xl font-semibold text-gray-900">
                                {formatNumber(row.compliance)}%
                            </div>
                            <div className="mt-2">
                                <ComplianceMeter value={row.compliance} />
                            </div>
                            <div className="mt-2 flex items-center gap-1.5 text-xs text-gray-500">
                                <span
                                    className="h-2 w-2 rounded-full"
                                    style={{ backgroundColor: semaphore.color }}
                                />
                                {semaphore.label}
                            </div>

                            <WeeklyStrip weekly={row.weekly} />

                            {row.plan_id && (
                                <Link
                                    href={route('plans.show', row.plan_id)}
                                    className="mt-4 text-sm font-medium text-green-700 hover:text-green-900"
                                >
                                    Ver plan →
                                </Link>
                            )}
                        </div>
                    );
                })}
            </div>
        </>
    );
}

export default function Dashboard({
    period,
    areasOverview,
    periods,
    myPlan,
}: DashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const isAdmin = auth.user.roles.includes('admin');

    const onPeriodChange = (value: string) => {
        router.get(
            route('dashboard'),
            value ? { period_id: value } : {},
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Inicio
                </h2>
            }
        >
            <Head title="Inicio" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-green-700 to-lime-600 p-6 shadow-sm">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-10"
                            style={{
                                backgroundImage:
                                    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M30 0l30 30-30 30L0 30z' fill='none' stroke='%23ffffff' stroke-width='1'/%3E%3C/svg%3E\")",
                            }}
                        />
                        {myPlan && (
                            <div className="absolute right-4 top-4 z-10">
                                <DueSoonBell
                                    activities={myPlan.due_soon}
                                    currentWeek={myPlan.current_week ?? 0}
                                    planId={myPlan.plan_id}
                                />
                            </div>
                        )}

                        <div className="relative flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p className="text-lg font-medium text-white">
                                    Hola, {auth.user.name}
                                </p>
                                <span className="mt-2 inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white">
                                    {auth.user.roles
                                        .map((r) => roleLabels[r])
                                        .join(', ') || 'Sin rol asignado'}
                                </span>
                                {period && (
                                    <p className="mt-2 text-sm text-green-50">
                                        Período actual: {MESES[period.month]}{' '}
                                        {period.year}
                                    </p>
                                )}
                            </div>

                            {isAdmin && (
                                <div className="flex gap-3">
                                    <Link
                                        href={route('admin.areas.index')}
                                        className="rounded-md bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm transition hover:bg-white/25"
                                    >
                                        Gestionar áreas
                                    </Link>
                                    <Link
                                        href={route('admin.users.index')}
                                        className="rounded-md bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm transition hover:bg-white/25"
                                    >
                                        Gestionar usuarios
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>

                    {periods && periods.length > 1 && (
                        <div className="flex items-center justify-end gap-2">
                            <label className="text-sm text-gray-600">
                                Período:
                            </label>
                            <select
                                className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                value={period?.id ?? ''}
                                onChange={(e) =>
                                    onPeriodChange(e.target.value)
                                }
                            >
                                {periods.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {MESES[p.month]} {p.year}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {areasOverview && (
                        <AreasOverview areasOverview={areasOverview} />
                    )}

                    {myPlan !== undefined && (
                        <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-medium text-gray-900">
                                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-700">
                                    <svg
                                        className="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                        />
                                    </svg>
                                </span>
                                Mi plan del mes
                            </h3>
                            {myPlan ? (
                                <>
                                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                                        <div>
                                            <div className="text-xs uppercase tracking-wide text-gray-500">
                                                Estado
                                            </div>
                                            <div className="mt-1 font-medium text-gray-900">
                                                {STATUS_LABEL[myPlan.status]}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="flex items-baseline justify-between text-xs uppercase tracking-wide text-gray-500">
                                                <span>Cumplimiento</span>
                                                <span className="text-base font-semibold text-gray-900 normal-case tracking-normal">
                                                    {formatNumber(
                                                        myPlan.compliance,
                                                    )}
                                                    %
                                                </span>
                                            </div>
                                            <div className="mt-2">
                                                <ComplianceMeter
                                                    value={myPlan.compliance}
                                                />
                                            </div>
                                            {myPlan.compliance_is_total && (
                                                <p className="mt-1 text-xs text-gray-400">
                                                    Incluye actividades
                                                    agregadas después de
                                                    aprobar el plan
                                                </p>
                                            )}
                                        </div>
                                        <Link
                                            href={route(
                                                'plans.show',
                                                myPlan.plan_id,
                                            )}
                                            className="rounded-md bg-green-700 px-4 py-2 text-center text-sm text-white hover:bg-green-800"
                                        >
                                            Ver mi plan completo
                                        </Link>
                                    </div>

                                    <div className="mt-4 flex items-center gap-2 text-sm text-gray-600">
                                        <span className="font-medium text-gray-900">
                                            {myPlan.activities_completed}
                                        </span>
                                        de
                                        <span className="font-medium text-gray-900">
                                            {myPlan.activities_total}
                                        </span>
                                        actividades cumplidas
                                    </div>

                                    {myPlan.groups.length > 0 && (
                                        <div className="mt-6 space-y-6 border-t border-gray-100 pt-4">
                                            {myPlan.groups.map((group) => (
                                                <div key={group.id}>
                                                    <div className="mb-2 flex items-center justify-between">
                                                        <h4 className="text-sm font-medium text-gray-900">
                                                            {group.name}
                                                        </h4>
                                                        <span className="text-xs text-gray-500">
                                                            Cumplimiento:{' '}
                                                            {formatNumber(
                                                                group.compliance,
                                                            )}
                                                            %
                                                        </span>
                                                    </div>
                                                    <GanttChart
                                                        activities={
                                                            group.activities
                                                        }
                                                        totalWeeks={
                                                            myPlan.total_weeks
                                                        }
                                                        currentWeek={
                                                            myPlan.current_week
                                                        }
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {myPlan.attention.length > 0 && (
                                        <div className="mt-6 border-t border-gray-100 pt-4">
                                            <h4 className="mb-2 text-sm font-medium text-gray-700">
                                                Actividades que necesitan
                                                atención
                                            </h4>
                                            <ul className="divide-y divide-gray-100">
                                                {myPlan.attention.map((a) => (
                                                    <li
                                                        key={a.id}
                                                        className="flex items-center justify-between py-2 text-sm"
                                                    >
                                                        <span>
                                                            {a.name} —{' '}
                                                            {a.responsible_name}
                                                        </span>
                                                        <ActivityStatusBadge
                                                            status={a.status}
                                                        />
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center">
                                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-lime-100 text-lime-700">
                                        <svg
                                            className="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15"
                                            />
                                        </svg>
                                    </span>
                                    <p className="text-sm font-medium text-gray-900">
                                        Todavía no armaste el plan de tu área
                                        {period &&
                                            ` para ${MESES[period.month]} ${period.year}`}
                                        .
                                    </p>
                                    <p className="max-w-sm text-sm text-gray-500">
                                        Creá el plan y empezá a agregar
                                        grupos y actividades para que tu
                                        equipo pueda reportar avance.
                                    </p>
                                    <button
                                        onClick={() =>
                                            router.post(
                                                route('plans.create-mine'),
                                                period
                                                    ? { period_id: period.id }
                                                    : {},
                                            )
                                        }
                                        className="mt-2 rounded-md bg-gradient-to-r from-green-700 to-lime-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-green-800 hover:to-lime-700"
                                    >
                                        Crear plan de trabajo
                                        {period &&
                                            ` — ${MESES[period.month]} ${period.year}`}
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
