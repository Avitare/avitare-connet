import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Area } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ areas }: { areas: Area[] }) {
    const handleDelete = (area: Area) => {
        if (confirm(`¿Eliminar el área "${area.name}"?`)) {
            router.delete(route('admin.areas.destroy', area.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Áreas
                </h2>
            }
        >
            <Head title="Áreas" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link
                            href={route('admin.areas.create')}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        >
                            Nueva área
                        </Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Slug
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Destino de requerimientos
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Activa
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {areas.map((area) => (
                                    <tr key={area.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {area.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {area.slug}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {area.is_gerencia && 'Gerencia'}
                                            {area.is_marketing && 'Marketing'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {area.active ? 'Sí' : 'No'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'admin.areas.edit',
                                                    area.id,
                                                )}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() =>
                                                    handleDelete(area)
                                                }
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
