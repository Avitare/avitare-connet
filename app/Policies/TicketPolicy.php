<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id || $this->manage($user, $ticket);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('admin') || $user->hasRole('ti');
    }

    public function confirm(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * El colaborador dueño del ticket puede cancelarlo mientras siga abierto
     * (antes de que alguien lo resuelva); una vez resuelto/cerrado, cancelar
     * ya no tiene sentido — ahí lo que corresponde es confirmar o calificar.
     */
    public function cancel(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id && in_array($ticket->status, Ticket::OPEN_STATUSES, true);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->manage($user, $ticket);
    }

    public function reprioritize(User $user, Ticket $ticket): bool
    {
        return $this->manage($user, $ticket);
    }
}
