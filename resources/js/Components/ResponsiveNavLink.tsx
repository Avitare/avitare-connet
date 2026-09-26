import { InertiaLinkProps, Link } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    icon: Icon,
    children,
    ...props
}: InertiaLinkProps & { active?: boolean; icon?: LucideIcon }) {
    return (
        <Link
            {...props}
            className={`flex w-full items-center gap-3 rounded-lg px-3 py-2.5 ${
                active
                    ? 'bg-green-50 text-green-800'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {Icon && (
                <Icon
                    className={'h-5 w-5 ' + (active ? 'text-green-700' : 'text-gray-400')}
                    strokeWidth={2}
                />
            )}
            {children}
        </Link>
    );
}
