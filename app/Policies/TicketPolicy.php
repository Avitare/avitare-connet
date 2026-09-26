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
        return $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('admin');
    }

    public function confirm(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
