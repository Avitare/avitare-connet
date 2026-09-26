import avitareLogo from '../../images/logo-avitare.png';

export default function AvitareLogo({
    className = '',
    markClassName = 'h-14 w-14 -my-3',
    showWordmark = true,
}: {
    className?: string;
    markClassName?: string;
    showWordmark?: boolean;
}) {
    return (
        <span className={`inline-flex items-center gap-2 ${className}`}>
            <img
                src={avitareLogo}
                alt="Avitare Grupo Inmobiliario"
                className={`shrink-0 object-contain ${markClassName}`}
            />
            {showWordmark && (
                <span className="hidden flex-col leading-none sm:flex">
                    <span className="text-sm font-bold tracking-tight text-gray-900">
                        Avitare Connect
                    </span>
                    <span className="hidden text-[10px] font-medium text-gray-400 lg:inline">
                        Tu espacio digital como colaborador
                    </span>
                </span>
            )}
        </span>
    );
}
