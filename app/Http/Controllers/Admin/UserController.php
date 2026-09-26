<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Area;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Usuarios/Index', [
            'users' => User::with('area', 'roles')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Usuarios/Create', [
            'areas' => Area::orderBy('name')->get(),
            'roles' => RoleSeeder::ROLES,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except('role')->all(),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        return to_route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Admin/Usuarios/Edit', [
            'user' => $user->load('roles'),
            'areas' => Area::orderBy('name')->get(),
            'roles' => RoleSeeder::ROLES,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->fill(collect($data)->except('role', 'password')->all());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return to_route('admin.users.index');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // `Gate::before` le da bypass total al admin sobre cualquier policy,
        // así que la regla "no podés borrarte a vos mismo" de UserPolicy::delete
        // nunca se evalúa para un admin — hay que exigirla acá también.
        if ($user->id === request()->user()->id) {
            abort(403, 'No podés eliminar tu propia cuenta.');
        }

        $user->delete();

        return to_route('admin.users.index');
    }
}
