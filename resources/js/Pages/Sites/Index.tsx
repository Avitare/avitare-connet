import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Site } from '@/types/models';
import { Head } from '@inertiajs/react';
import { Globe } from 'lucide-react';

export default function Index({ sites }: { sites: Site[] }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Mis sitios
                </h2>
            }
        >
            <Head title="Mis sitios" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <p className="mb-6 px-1 text-sm text-gray-500 sm:px-0">
                        Accesos rápidos a los demás sistemas de Avitare para
                        colaboradores.
                    </p>

                    {sites.length === 0 && (
                        <p className="px-1 text-sm text-gray-500 sm:px-0">
                            Todavía no hay sitios configurados.
                        </p>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {sites.map((site) => (
                            <a
                                key={site.id}
                                href={site.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="group relative flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-green-200 hover:shadow-md"
                            >
                                <span className="flex h-11 w-11 items-center justify-center rounded-lg bg-gradient-to-br from-green-700 to-lime-500 text-white">
                                    <Globe
                                        className="h-6 w-6"
                                        strokeWidth={2}
                                    />
                                </span>

                                <span>
                                    <span className="block text-sm font-semibold text-gray-900">
                                        {site.name}
                                    </span>
                                    {site.description && (
                                        <span className="mt-0.5 block text-sm text-gray-500">
                                            {site.description}
                                        </span>
                                    )}
                                </span>
                            </a>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
