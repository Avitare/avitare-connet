import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Bot, Building2, Car, Clock, LucideIcon, Truck, Users } from 'lucide-react';

interface SiteLink {
    name: string;
    description: string;
    icon: LucideIcon;
    href: string | null;
}

const SITES: SiteLink[] = [
    {
        name: 'Movilidad',
        description: 'Gestión de vehículos y traslados del equipo.',
        icon: Car,
        href: 'https://movilidad.grupoavitare.com/',
    },
    {
        name: 'Automatizador',
        description: 'Flujos y tareas automatizadas de la operación.',
        icon: Bot,
        href: 'https://automatizador.grupoavitare.com/',
    },
    {
        name: 'Solti',
        description: 'Sistema ERP de gestión empresarial.',
        icon: Building2,
        href: 'https://sistema-avitare.com/erp-login',
    },
    {
        name: 'Vita',
        description: 'CRM de gestión comercial y clientes.',
        icon: Users,
        href: 'https://vita.grupoavitare.com/login',
    },
    {
        name: 'Logística',
        description: 'Seguimiento de despachos e inventario.',
        icon: Truck,
        href: 'https://logistica.grupoavitare.com/login',
    },
];

export default function Index() {
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {SITES.map(({ name, description, icon: Icon, href }) => {
                            const enabled = Boolean(href);

                            return (
                                <a
                                    key={name}
                                    href={href ?? undefined}
                                    target={enabled ? '_blank' : undefined}
                                    rel={enabled ? 'noopener noreferrer' : undefined}
                                    aria-disabled={!enabled}
                                    className={
                                        'group relative flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition ' +
                                        (enabled
                                            ? 'hover:-translate-y-0.5 hover:border-green-200 hover:shadow-md'
                                            : 'cursor-not-allowed opacity-70')
                                    }
                                >
                                    {!enabled && (
                                        <span className="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">
                                            <Clock className="h-3 w-3" />
                                            Próximamente
                                        </span>
                                    )}

                                    <span className="flex h-11 w-11 items-center justify-center rounded-lg bg-gradient-to-br from-green-700 to-lime-500 text-white">
                                        <Icon className="h-6 w-6" strokeWidth={2} />
                                    </span>

                                    <span>
                                        <span className="block text-sm font-semibold text-gray-900">
                                            {name}
                                        </span>
                                        <span className="mt-0.5 block text-sm text-gray-500">
                                            {description}
                                        </span>
                                    </span>
                                </a>
                            );
                        })}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
