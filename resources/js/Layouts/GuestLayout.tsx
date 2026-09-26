import avitareLogo from '../../images/logo-avitare.png';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

const CHEVRON_PATTERN =
    "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Cpath d='M8 30 L24 16 L40 30' fill='none' stroke='%23166534' stroke-width='3' stroke-linecap='round' stroke-linejoin='round' opacity='0.08'/%3E%3C/svg%3E\")";

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-green-50 px-4 py-12">
            <div className="pointer-events-none absolute inset-0">
                <div
                    className="absolute inset-0"
                    style={{
                        backgroundImage: CHEVRON_PATTERN,
                        backgroundSize: '48px 48px',
                    }}
                />
                <div className="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-lime-300/40 blur-3xl" />
                <div className="absolute -right-24 -bottom-40 h-[28rem] w-[28rem] rounded-full bg-green-400/30 blur-3xl" />
            </div>

            <div className="relative w-full max-w-md">
                <div className="rounded-3xl border border-white/60 bg-white/80 p-8 shadow-2xl backdrop-blur-xl sm:p-10">
                    <Link
                        href="/"
                        className="mx-auto mb-2 flex flex-col items-center"
                    >
                        <img
                            src={avitareLogo}
                            alt="Avitare Grupo Inmobiliario"
                            className="h-28 w-28 object-contain"
                        />
                        <span className="-mt-2 text-lg font-bold tracking-tight text-gray-900">
                            Avitare Connect
                        </span>
                        <span className="text-xs font-medium text-green-700">
                            Tu espacio digital como colaborador
                        </span>
                    </Link>

                    {children}
                </div>

                <p className="mt-6 text-center text-xs text-green-900/60">
                    © {new Date().getFullYear()} Avitare Grupo Inmobiliario
                </p>
            </div>
        </div>
    );
}
