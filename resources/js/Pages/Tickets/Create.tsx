import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    TicketCategory,
    TicketPriority,
    TicketTypeOption,
} from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { Send, X } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Create({
    categories,
    types,
    priorities,
    preselectedCategory,
}: {
    categories: TicketCategory[];
    types: TicketTypeOption[];
    priorities: TicketPriority[];
    preselectedCategory: number | null;
}) {
    const { data, setData, post, processing, errors } = useForm({
        category_id: preselectedCategory ? String(preselectedCategory) : '',
        type_id: '',
        priority_id: '',
        subject: '',
        description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Crear ticket
                </h2>
            }
        >
            <Head title="Crear ticket" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                    >
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel value="Categoría" />
                                <select
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                    value={data.category_id}
                                    onChange={(e) =>
                                        setData('category_id', e.target.value)
                                    }
                                >
                                    <option value="">Selecciona…</option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.icon
                                                ? `${category.icon} `
                                                : ''}
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div>
                                <InputLabel value="Tipo" />
                                <select
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                    value={data.type_id}
                                    onChange={(e) =>
                                        setData('type_id', e.target.value)
                                    }
                                >
                                    <option value="">Selecciona…</option>
                                    {types.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type_id} />
                            </div>

                            <div>
                                <InputLabel value="Prioridad" />
                                <select
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                    value={data.priority_id}
                                    onChange={(e) =>
                                        setData('priority_id', e.target.value)
                                    }
                                >
                                    <option value="">Selecciona…</option>
                                    {priorities.map((priority) => (
                                        <option
                                            key={priority.id}
                                            value={priority.id}
                                        >
                                            {priority.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.priority_id} />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Asunto" />
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600"
                                value={data.subject}
                                onChange={(e) =>
                                    setData('subject', e.target.value)
                                }
                            />
                            <InputError message={errors.subject} />
                        </div>

                        <div>
                            <InputLabel value="Descripción" />
                            <textarea
                                className="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"
                                rows={5}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex gap-2">
                            <PrimaryButton
                                disabled={processing}
                                className="gap-1.5"
                            >
                                <Send className="h-3.5 w-3.5" />
                                Crear ticket
                            </PrimaryButton>
                            <Link href={route('tickets.index')}>
                                <SecondaryButton
                                    type="button"
                                    className="gap-1.5"
                                >
                                    <X className="h-3.5 w-3.5" />
                                    Cancelar
                                </SecondaryButton>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
