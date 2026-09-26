import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { MonthlyPlanSummary } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface PeriodOption {
    id: number;
    year: number;
    month: number;
}

const MESES = [
    '',
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

const STATUS_LABEL: Record<string, string> = {
    borrador: 'Borrador',
    vigente: 'Vigente',
    cerrado: 'Cerrado',
};

function CreatePlanForm() {
    const today = new Date();
    const [year, setYear] = useState(today.getFullYear());
    const [month, setMonth] = useState(today.getMonth() + 1);
    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('plans.create-mine'),
            { year, month },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm"
        >
            <div>
                <label className="block text-xs text-gray-500">Mes</label>
                <select
                    className="mt-1 rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                    value={month}
                    onChange={(e) => setMonth(Number(e.target.value))}
                >
                    {MESES.slice(1).map((name, i) => (
                        <option key={name} value={i + 1}>
                            {name}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label className="block text-xs text-gray-500">Año</label>
                <input
                    type="number"
                    className="mt-1 w-24 rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                    value={year}
                    onChange={(e) => setYear(Number(e.target.value))}
                />
            </div>
            <PrimaryButton disabled={processing}>
                Crear plan de trabajo
            </PrimaryButton>
        </form>
    );
}

export default function Index({
    plans,
    periods,
    selectedPeriodId,
    canCreatePlan,
}: {
    plans: MonthlyPlanSummary[];
    periods: PeriodOption[];
    selectedPeriodId: number | null;
    canCreatePlan: boolean;
}) {
    const onPeriodChange = (value: string) => {
        router.get(
            route('plans.index'),
            value ? { period_id: value } : {},
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Planes mensuales
                </h2>
            }
        >
            <Head title="Planes" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
                    {canCreatePlan && <CreatePlanForm />}

                    <div className="flex items-center gap-2 px-1 sm:px-0">
                        <label className="text-sm text-gray-600">
                            Período:
                        </label>
                        <select
                            className="rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                            value={selectedPeriodId ?? ''}
                            onChange={(e) => onPeriodChange(e.target.value)}
                        >
                            <option value="">Todos los períodos</option>
                            {periods.map((period) => (
                                <option key={period.id} value={period.id}>
                                    {MESES[period.month]} {period.year}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Área
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Período
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {plans.map((plan) => (
                                    <tr key={plan.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {plan.area.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {MESES[plan.period.month]}{' '}
                                            {plan.period.year}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {STATUS_LABEL[plan.status]}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'plans.show',
                                                    plan.id,
                                                )}
                                                className="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Ver
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {plans.length === 0 && (
                                    <tr>
                                        <td
                                            className="px-6 py-4 text-sm text-gray-500"
                                            colSpan={4}
                                        >
                                            No hay planes todavía.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
