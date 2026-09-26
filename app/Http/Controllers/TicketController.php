<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketType;
use App\Services\Tickets\TicketService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tickets = Ticket::query()
            ->where('user_id', $user->id)
            ->with('category:id,name,icon')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'status' => $ticket->status,
                'subject' => $ticket->subject,
                'category' => $ticket->category->only(['id', 'name', 'icon']),
                'created_at' => $ticket->created_at,
            ]);

        return Inertia::render('Tickets/Index', [
            'categories' => Category::orderBy('name')->get(['id', 'name', 'icon']),
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Tickets/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name', 'icon']),
            'types' => TicketType::orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::orderBy('rank')->get(['id', 'name', 'color', 'sla_response_minutes', 'sla_resolution_minutes', 'rank']),
            'preselectedCategory' => $request->integer('category') ?: null,
        ]);
    }

    public function store(Request $request, TicketService $service): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'type_id' => ['required', 'integer', Rule::exists('ticket_types', 'id')],
            'priority_id' => ['required', 'integer', Rule::exists('priorities', 'id')],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $ticket = $service->create($data, $request->user());

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['user', 'area', 'category', 'type', 'priority', 'attachments.uploader', 'events.user']);

        return Inertia::render('Tickets/Show', [
            'ticket' => $this->present($ticket),
            'can' => [
                'manage' => $request->user()->can('manage', $ticket),
                'confirm' => $request->user()->can('confirm', $ticket),
            ],
        ]);
    }

    public function comment(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $data = $request->validate(['body' => ['required', 'string']]);

        $service->addComment($ticket, $request->user(), $data['body']);

        return back();
    }

    public function attachment(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $data = $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $service->addAttachment($ticket, $request->user(), $data['file']);

        return back();
    }

    public function confirm(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('confirm', $ticket);

        try {
            $service->confirm($ticket, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        return back();
    }

    public function rate(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('confirm', $ticket);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $service->rate($ticket, $data['rating'], $data['comment'] ?? null);

        return back();
    }

    public function status(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        $data = $request->validate([
            'status' => ['required', 'in:EN_PROCESO,ESPERANDO_USUARIO,CANCELADO'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $service->changeStatus($ticket, $data['status'], $request->user(), $data['note'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        return back();
    }

    public function resolve(Request $request, Ticket $ticket, TicketService $service): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        $data = $request->validate(['solution' => ['required', 'string']]);

        try {
            $service->resolve($ticket, $request->user(), $data['solution']);
        } catch (DomainException $e) {
            return back()->withErrors(['ticket' => $e->getMessage()]);
        }

        return back();
    }

    public function inbox(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->hasRole('admin'), 403);

        $status = $request->query('status', 'abiertos');

        $query = Ticket::query()->with(['user', 'area', 'category', 'type', 'priority', 'attachments.uploader', 'events.user']);

        $query = match ($status) {
            'resueltos' => $query->where('status', 'RESUELTO'),
            'cerrados' => $query->whereIn('status', ['CERRADO', 'CANCELADO']),
            'todos' => $query,
            default => $query->whereIn('status', Ticket::OPEN_STATUSES),
        };

        return Inertia::render('Tickets/Inbox', [
            'tickets' => $query->orderByDesc('id')->get()->map($this->present(...)),
            'statusFilter' => $status,
        ]);
    }

    public function downloadAttachment(TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment->ticket);

        return Storage::disk('local')->response($attachment->path, $attachment->original_name);
    }

    private function present(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'code' => $ticket->code,
            'status' => $ticket->status,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'user' => $ticket->user->only(['id', 'name']),
            'area' => $ticket->area?->only(['id', 'name']),
            'category' => $ticket->category->only(['id', 'name', 'icon']),
            'type' => $ticket->type->only(['id', 'name']),
            'priority' => $ticket->priority->only(['id', 'name', 'color', 'sla_response_minutes', 'sla_resolution_minutes', 'rank']),
            'sla_response_due_at' => $ticket->sla_response_due_at,
            'sla_resolution_due_at' => $ticket->sla_resolution_due_at,
            'first_response_at' => $ticket->first_response_at,
            'resolved_at' => $ticket->resolved_at,
            'closed_at' => $ticket->closed_at,
            'satisfaction_rating' => $ticket->satisfaction_rating,
            'satisfaction_comment' => $ticket->satisfaction_comment,
            'created_at' => $ticket->created_at,
            'events' => $ticket->events->map(fn ($event) => [
                'id' => $event->id,
                'type' => $event->type,
                'body' => $event->body,
                'user' => $event->user?->only(['id', 'name']),
                'created_at' => $event->created_at,
            ]),
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'uploaded_by' => $attachment->uploader?->only(['id', 'name']),
                'download_url' => route('tickets.attachments.download', $attachment->id),
            ]),
        ];
    }
}
