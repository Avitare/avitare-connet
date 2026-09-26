import { InertiaLinkProps, Link } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export default function NavLink({
    active = false,
    className = '',
    icon: Icon,
    children,
    ...props
}: InertiaLinkProps & { active: boolean; icon?: LucideIcon }) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ' +
                (active
                    ? 'bg-green-50 text-green-800'
                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800') +
                ' ' +
                className
            }
        >
            {Icon && (
                <Icon
                    className={
                        'h-4 w-4 ' + (active ? 'text-green-700' : 'text-gray-400')
                    }
                    strokeWidth={2}
                />
            )}
            {children}
        </Link>
    );
}
