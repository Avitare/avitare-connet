import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { slugify } from '@/Support/slug';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        is_gerencia: false as boolean,
        is_marketing: false as boolean,
        active: true as boolean,
    });
    const [slugEdited, setSlugEdited] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.areas.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nueva área
                </h2>
            }
        >
            <Head title="Nueva área" />

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
                                    onChange={(e) => {
                                        const name = e.target.value;
                                        setData((current) => ({
                                            ...current,
                                            name,
                                            slug: slugEdited
                                                ? current.slug
                                                : slugify(name),
                                        }));
                                    }}
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="slug" value="Slug" />
                                <TextInput
                                    id="slug"
                                    className="mt-1 block w-full"
                                    value={data.slug}
                                    onChange={(e) => {
                                        setSlugEdited(true);
                                        setData('slug', e.target.value);
                                    }}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Se genera automáticamente a partir del
                                    nombre. Puedes ajustarlo si lo necesitas.
                                </p>
                                <InputError
                                    message={errors.slug}
                                    className="mt-2"
                                />
                            </div>

                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.is_gerencia}
                                    onChange={(e) =>
                                        setData(
                                            'is_gerencia',
                                            e.target.checked,
                                        )
                                    }
                                    className="rounded border-gray-300"
                                />
                                <span className="ml-2 text-sm text-gray-600">
                                    Es el área "Gerencia" (destino por defecto
                                    de servicio y presupuesto)
                                </span>
                            </label>

                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.is_marketing}
                                    onChange={(e) =>
                                        setData(
                                            'is_marketing',
                                            e.target.checked,
                                        )
                                    }
                                    className="rounded border-gray-300"
                                />
                                <span className="ml-2 text-sm text-gray-600">
                                    Es el área "Marketing" (destino por
                                    defecto de requerimientos de marketing)
                                </span>
                            </label>

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
                                    Activa
                                </span>
                            </label>

                            <div className="flex gap-2">
                                <PrimaryButton disabled={processing}>
                                    Guardar
                                </PrimaryButton>
                                <Link href={route('admin.areas.index')}>
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
