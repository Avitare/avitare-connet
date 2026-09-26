import AvitareLogo from '@/Components/AvitareLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Role } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ChevronDown,
    ClipboardList,
    FileText,
    Home,
    Inbox,
    LayoutGrid,
    LogOut,
    Menu,
    Settings,
    Ticket,
    Users,
    UserCircle,
    Wrench,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useState } from 'react';

const roleLabels: Record<Role, string> = {
    admin: 'Super Admin',
    gerencia: 'Gerencia General',
    jefe_area: 'Jefe de área',
    marketing: 'Marketing',
    ti: 'Soporte TI',
};

function CountBadge({ count }: { count: number }) {
    if (count <= 0) {
        return null;
    }

    return (
        <span className="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white">
            {count > 99 ? '99+' : count}
        </span>
    );
}

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, ticketAlerts } = usePage().props;
    const user = auth.user;
    const isAdmin = user.roles.includes('admin');
    const isTi = user.roles.includes('ti');
    const canManageTickets = isAdmin || isTi;
    const canRequest = user.area_id !== null;
    const canSeeRequerimientosInbox =
        isAdmin ||
        user.roles.includes('gerencia') ||
        user.roles.includes('marketing');
    const isAdminSectionActive =
        route().current('tickets.inbox') ||
        route().current('admin.categories.*') ||
        route().current('admin.areas.*') ||
        route().current('admin.sites.*') ||
        route().current('admin.users.*') ||
        route().current('admin.settings.*');
    const roleLabel = roleLabels[user.roles[0]] ?? null;
    const initials = user.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center">
                            <Link
                                href="/"
                                className="flex shrink-0 items-center"
                            >
                                <AvitareLogo />
                            </Link>

                            <div className="hidden sm:ms-8 sm:flex sm:items-center sm:gap-1">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                    icon={Home}
                                >
                                    Inicio
                                </NavLink>
                                <NavLink
                                    href={route('plans.index')}
                                    active={route().current('plans.*')}
                                    icon={ClipboardList}
                                >
                                    Planes
                                </NavLink>
                                <NavLink
                                    href={route('sites.index')}
                                    active={route().current('sites.*')}
                                    icon={LayoutGrid}
                                >
                                    Mis sitios
                                </NavLink>
                                {!canManageTickets && (
                                    <NavLink
                                        href={route('tickets.index')}
                                        active={route().current(
                                            'tickets.index',
                                        )}
                                        icon={Ticket}
                                    >
                                        Mis tickets
                                        <CountBadge count={ticketAlerts} />
                                    </NavLink>
                                )}
                                {canRequest && (
                                    <NavLink
                                        href={route('requerimientos.index')}
                                        active={route().current(
                                            'requerimientos.index',
                                        )}
                                        icon={FileText}
                                    >
                                        Realizar requerimientos
                                    </NavLink>
                                )}
                                {canSeeRequerimientosInbox && (
                                    <NavLink
                                        href={route('requerimientos.inbox')}
                                        active={route().current(
                                            'requerimientos.inbox',
                                        )}
                                        icon={Inbox}
                                    >
                                        Bandeja de requerimientos
                                    </NavLink>
                                )}
                                {isAdmin ? (
                                    <Dropdown>
                                        <Dropdown.Trigger>
                                            <button
                                                type="button"
                                                className={
                                                    'inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ' +
                                                    (isAdminSectionActive
                                                        ? 'bg-green-50 text-green-800'
                                                        : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800')
                                                }
                                            >
                                                <Wrench
                                                    className={
                                                        'h-4 w-4 ' +
                                                        (isAdminSectionActive
                                                            ? 'text-green-700'
                                                            : 'text-gray-400')
                                                    }
                                                    strokeWidth={2}
                                                />
                                                Administración
                                                <CountBadge
                                                    count={ticketAlerts}
                                                />
                                                <ChevronDown className="h-3.5 w-3.5" />
                                            </button>
                                        </Dropdown.Trigger>
                                        <Dropdown.Content
                                            align="left"
                                            width="56"
                                        >
                                            <Dropdown.Link
                                                href={route('tickets.inbox')}
                                                className="flex items-center justify-between"
                                            >
                                                Bandeja de tickets
                                                <CountBadge
                                                    count={ticketAlerts}
                                                />
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route(
                                                    'admin.categories.index',
                                                )}
                                            >
                                                Categorías
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route(
                                                    'admin.areas.index',
                                                )}
                                            >
                                                Áreas
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route(
                                                    'admin.sites.index',
                                                )}
                                            >
                                                Sitios
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route(
                                                    'admin.users.index',
                                                )}
                                            >
                                                Usuarios
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route(
                                                    'admin.settings.edit',
                                                )}
                                            >
                                                Configuración
                                            </Dropdown.Link>
                                        </Dropdown.Content>
                                    </Dropdown>
                                ) : (
                                    isTi && (
                                        <>
                                            <NavLink
                                                href={route('tickets.inbox')}
                                                active={route().current(
                                                    'tickets.inbox',
                                                )}
                                                icon={Inbox}
                                            >
                                                Bandeja de tickets
                                                <CountBadge
                                                    count={ticketAlerts}
                                                />
                                            </NavLink>
                                            <NavLink
                                                href={route(
                                                    'admin.categories.index',
                                                )}
                                                active={route().current(
                                                    'admin.categories.*',
                                                )}
                                                icon={Ticket}
                                            >
                                                Categorías
                                            </NavLink>
                                        </>
                                    )
                                )}
                            </div>
                        </div>

                        <div className="hidden sm:flex sm:items-center sm:gap-3">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="flex items-center gap-1 rounded-full p-1 transition duration-150 ease-in-out hover:bg-gray-50"
                                    >
                                        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-green-700 to-lime-500 text-xs font-semibold text-white">
                                            {initials}
                                        </span>
                                        <ChevronDown className="h-4 w-4 text-gray-400" />
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <div className="border-b border-gray-100 px-4 py-3">
                                        <div className="text-sm font-medium text-gray-800">
                                            {user.name}
                                        </div>
                                        {roleLabel && (
                                            <div className="text-xs text-gray-500">
                                                {roleLabel}
                                            </div>
                                        )}
                                    </div>
                                    {isAdmin && (
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                            className="flex items-center gap-2"
                                        >
                                            <UserCircle className="h-4 w-4 text-gray-400" />
                                            Perfil
                                        </Dropdown.Link>
                                    )}
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        className="flex items-center gap-2"
                                    >
                                        <LogOut className="h-4 w-4 text-gray-400" />
                                        Cerrar sesión
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                {showingNavigationDropdown ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' border-t border-gray-100 sm:hidden'
                    }
                >
                    <div className="space-y-1 px-3 pb-3 pt-1">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                            icon={Home}
                        >
                            Inicio
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('plans.index')}
                            active={route().current('plans.*')}
                            icon={ClipboardList}
                        >
                            Planes
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('sites.index')}
                            active={route().current('sites.*')}
                            icon={LayoutGrid}
                        >
                            Mis sitios
                        </ResponsiveNavLink>
                        {!canManageTickets && (
                            <ResponsiveNavLink
                                href={route('tickets.index')}
                                active={route().current('tickets.index')}
                                icon={Ticket}
                            >
                                Mis tickets
                                <CountBadge count={ticketAlerts} />
                            </ResponsiveNavLink>
                        )}
                        {canRequest && (
                            <ResponsiveNavLink
                                href={route('requerimientos.index')}
                                active={route().current(
                                    'requerimientos.index',
                                )}
                                icon={FileText}
                            >
                                Realizar requerimientos
                            </ResponsiveNavLink>
                        )}
                        {canSeeRequerimientosInbox && (
                            <ResponsiveNavLink
                                href={route('requerimientos.inbox')}
                                active={route().current(
                                    'requerimientos.inbox',
                                )}
                                icon={Inbox}
                            >
                                Bandeja de requerimientos
                            </ResponsiveNavLink>
                        )}
                        {canManageTickets && (
                            <>
                                <ResponsiveNavLink
                                    href={route('tickets.inbox')}
                                    active={route().current('tickets.inbox')}
                                    icon={Inbox}
                                >
                                    Bandeja de tickets
                                    <CountBadge count={ticketAlerts} />
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.categories.index')}
                                    active={route().current(
                                        'admin.categories.*',
                                    )}
                                    icon={Ticket}
                                >
                                    Categorías
                                </ResponsiveNavLink>
                            </>
                        )}
                        {isAdmin && (
                            <>
                                <ResponsiveNavLink
                                    href={route('admin.areas.index')}
                                    active={route().current('admin.areas.*')}
                                    icon={Building2}
                                >
                                    Áreas
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.users.index')}
                                    active={route().current('admin.users.*')}
                                    icon={Users}
                                >
                                    Usuarios
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.settings.edit')}
                                    active={route().current('admin.settings.*')}
                                    icon={Settings}
                                >
                                    Configuración
                                </ResponsiveNavLink>
                            </>
                        )}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="flex items-center gap-3 px-4">
                            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-green-700 to-lime-500 text-xs font-semibold text-white">
                                {initials}
                            </span>
                            <div>
                                <div className="text-base font-medium text-gray-800">
                                    {user.name}
                                </div>
                                <div className="text-sm font-medium text-gray-500">
                                    {roleLabel ?? user.email}
                                </div>
                            </div>
                        </div>

                        <div className="mt-3 space-y-1 px-3">
                            {isAdmin && (
                                <ResponsiveNavLink
                                    href={route('profile.edit')}
                                    icon={UserCircle}
                                >
                                    Perfil
                                </ResponsiveNavLink>
                            )}
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                                icon={LogOut}
                            >
                                Cerrar sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
