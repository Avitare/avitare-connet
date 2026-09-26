import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { TicketCategory } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({
    categories,
}: {
    categories: TicketCategory[];
}) {
    const handleDelete = (category: TicketCategory) => {
        if (confirm(`¿Eliminar la categoría "${category.name}"?`)) {
            router.delete(route('admin.categories.destroy', category.id), {
                onError: (errors) => {
                    if (errors.category) {
                        alert(errors.category);
                    }
                },
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Categorías de tickets
                </h2>
            }
        >
            <Head title="Categorías de tickets" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link
                            href={route('admin.categories.create')}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        >
                            Nueva categoría
                        </Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Icono
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {categories.map((category) => (
                                    <tr key={category.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-lg">
                                            {category.icon ?? '🎫'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {category.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'admin.categories.edit',
                                                    category.id,
                                                )}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() =>
                                                    handleDelete(category)
                                                }
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {categories.length === 0 && (
                                    <tr>
                                        <td
                                            className="px-6 py-4 text-sm text-gray-500"
                                            colSpan={3}
                                        >
                                            Todavía no hay categorías.
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
