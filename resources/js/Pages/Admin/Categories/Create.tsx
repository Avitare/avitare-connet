import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        icon: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.categories.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nueva categoría
                </h2>
            }
        >
            <Head title="Nueva categoría" />

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
                                <InputLabel
                                    htmlFor="icon"
                                    value="Icono (emoji)"
                                />
                                <TextInput
                                    id="icon"
                                    className="mt-1 block w-full"
                                    placeholder="💻"
                                    value={data.icon}
                                    onChange={(e) =>
                                        setData('icon', e.target.value)
                                    }
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Se muestra en la tarjeta de "Mis tickets".
                                    Opcional.
                                </p>
                                <InputError
                                    message={errors.icon}
                                    className="mt-2"
                                />
                            </div>

                            <div className="flex gap-2">
                                <PrimaryButton disabled={processing}>
                                    Guardar
                                </PrimaryButton>
                                <Link href={route('admin.categories.index')}>
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
