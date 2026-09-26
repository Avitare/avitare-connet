import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { AdminUser, Area } from '@/types/models';
import { Role } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const roleLabels: Record<Role, string> = {
    admin: 'Super Admin',
    gerencia: 'Gerencia General',
    jefe_area: 'Jefe de área',
    marketing: 'Marketing',
};

export default function Edit({
    user,
    areas,
    roles,
}: {
    user: AdminUser;
    areas: Area[];
    roles: Role[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        area_id: user.area_id,
        position: user.position ?? '',
        active: user.active,
        role: (user.roles[0]?.name ?? '') as Role | '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.users.update', user.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar usuario
                </h2>
            }
        >
            <Head title="Editar usuario" />

            <div className="py-12">
                <div className="mx-auto max-w-xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="name" value="Nombre" />
                                <TextInput
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.email}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="password"
                                    value="Nueva contraseña (opcional)"
                                />
                                <TextInput
                                    id="password"
                                    type="password"
                                    className="mt-1 block w-full"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.password}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="area_id" value="Área" />
                                <select
                                    id="area_id"
                                    className="mt-1 block w-full rounded-md border-gray-300"
                                    value={data.area_id ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'area_id',
                                            e.target.value
                                                ? Number(e.target.value)
                                                : null,
                                        )
                                    }
                                >
                                    <option value="">Sin área</option>
                                    {areas.map((area) => (
                                        <option key={area.id} value={area.id}>
                                            {area.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.area_id}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="position"
                                    value="Cargo"
                                />
                                <TextInput
                                    id="position"
                                    className="mt-1 block w-full"
                                    value={data.position}
                                    onChange={(e) =>
                                        setData('position', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.position}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="role" value="Rol" />
                                <select
                                    id="role"
                                    className="mt-1 block w-full rounded-md border-gray-300"
                                    value={data.role}
                                    onChange={(e) =>
                                        setData(
                                            'role',
                                            e.target.value as Role,
                                        )
                                    }
                                >
                                    <option value="">Selecciona un rol</option>
                                    {roles.map((role) => (
                                        <option key={role} value={role}>
                                            {roleLabels[role]}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.role}
                                    className="mt-2"
                                />
                            </div>

                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.active}
                                    onChange={(e) =>
                                        setData('active', e.target.checked)
                                    }
                                    className="rounded border-gray-300"
                                />
                                <span className="ml-2 text-sm text-gray-600">
                                    Activo
                                </span>
                            </label>

                            <div className="flex gap-2">
                                <PrimaryButton disabled={processing}>
                                    Guardar
                                </PrimaryButton>
                                <Link href={route('admin.users.index')}>
                                    <SecondaryButton type="button">
                                        Cancelar
                                    </SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
