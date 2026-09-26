<?php

namespace App\Http\Middleware;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    ...$user->only('id', 'name', 'email', 'email_verified_at', 'area_id', 'position'),
                    'roles' => $user->getRoleNames(),
                ] : null,
            ],
            'ticketAlerts' => $user ? $this->ticketAlerts($user) : 0,
        ];
    }

    /**
     * Contador simple para la campanita de tickets en el nav: para TI/admin,
     * cuántos tickets abiertos necesitan atención (sin asignar o asignados
     * a ellos mismos); para el resto, cuántos de sus propios tickets están
     * resueltos y esperando que confirmen el cierre.
     */
    private function ticketAlerts(User $user): int
    {
        if ($user->hasRole('admin') || $user->hasRole('ti')) {
            return Ticket::query()
                ->whereIn('status', Ticket::OPEN_STATUSES)
                ->where(fn ($q) => $q->whereNull('assigned_to')->orWhere('assigned_to', $user->id))
                ->count();
        }

        return Ticket::query()
            ->where('user_id', $user->id)
            ->where('status', 'RESUELTO')
            ->count();
    }
}
