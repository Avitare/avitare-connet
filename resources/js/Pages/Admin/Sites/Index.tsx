import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Site } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ sites }: { sites: Site[] }) {
    const handleToggle = (site: Site) => {
        router.patch(route('admin.sites.toggle', site.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Sitios
                </h2>
            }
        >
            <Head title="Sitios" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link
                            href={route('admin.sites.create')}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        >
                            Nuevo sitio
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
                                        Enlace
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {sites.map((site) => (
                                    <tr key={site.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {site.name}
                                        </td>
                                        <td className="max-w-xs truncate px-6 py-4 text-sm text-gray-500">
                                            {site.url}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <span
                                                className={
                                                    'rounded-full px-2 py-0.5 text-xs font-medium ' +
                                                    (site.active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-500')
                                                }
                                            >
                                                {site.active
                                                    ? 'Habilitado'
                                                    : 'Deshabilitado'}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'admin.sites.edit',
                                                    site.id,
                                                )}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() =>
                                                    handleToggle(site)
                                                }
                                                className="text-gray-600 hover:text-gray-900"
                                            >
                                                {site.active
                                                    ? 'Deshabilitar'
                                                    : 'Habilitar'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {sites.length === 0 && (
                                    <tr>
                                        <td
                                            className="px-6 py-4 text-sm text-gray-500"
                                            colSpan={4}
                                        >
                                            Todavía no hay sitios.
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
