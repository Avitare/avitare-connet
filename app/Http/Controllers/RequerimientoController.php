<?php

namespace App\Http\Controllers;

use App\Enums\RequerimientoStatus;
use App\Enums\RequerimientoType;
use App\Models\Activity;
use App\Models\Requerimiento;
use App\Models\RequerimientoMaterial;
use App\Services\Requerimientos\RequerimientoWorkflowService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequerimientoController extends Controller
{
    private const RELATIONS = ['user', 'area', 'activity', 'decidedBy', 'statusLogs.performedBy', 'materials', 'budgetItems'];

    private const MATERIAL_RULES = [
        'needed_by' => ['nullable', 'date'],
        'materials' => ['required', 'array', 'min:1'],
        'materials.*.material' => ['required', 'string', 'max:255'],
        'materials.*.especificaciones' => ['nullable', 'string'],
        'materials.*.publico_objetivo' => ['nullable', 'string', 'max:255'],
        'materials.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ];

    private const BUDGET_RULES = [
        'items' => ['required', 'array', 'min:1'],
        'items.*.objetivo' => ['required', 'string', 'max:255'],
        'items.*.monto_solicitado' => ['required', 'numeric', 'min:0'],
        'items.*.fecha_requerida' => ['nullable', 'date'],
        'items.*.especificacion_uso' => ['nullable', 'string'],
    ];

    /**
     * Cada tipo de requerimiento tiene su propio formulario (spec: formatos
     * distintos por tipo, con su propio documento controlado) — match
     * exhaustivo de los 4 tipos reales. Servicio y T.I. comparten el mismo
     * formulario genérico (detalle/solicitud + especificaciones).
     */
    private function validationRulesFor(string $type): array
    {
        return match ($type) {
            'marketing' => self::MATERIAL_RULES,
            'presupuesto' => self::BUDGET_RULES,
            default => [
                'detail' => ['required', 'string'],
                'especificaciones' => ['nullable', 'string'],
            ],
        };
    }

    /**
     * Foto fija del documento controlado vigente al crear el requerimiento
     * (ver constantes en el modelo).
     */
    private function formatFor(string $type): ?array
    {
        return match ($type) {
            'marketing' => Requerimiento::MARKETING_FORMAT,
            'servicio' => Requerimiento::SERVICIO_FORMAT,
            'presupuesto' => Requerimiento::PRESUPUESTO_FORMAT,
            'ti' => Requerimiento::TI_FORMAT,
            default => null,
        };
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $mine = Requerimiento::query()
            ->where('user_id', $user->id)
            ->with(self::RELATIONS)
            ->orderByDesc('id')
            ->get();

        $activities = Activity::query()
            ->whereHas('planGroup.monthlyPlan', fn ($query) => $query->where('area_id', $user->area_id))
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return Inertia::render('Requerimientos/Index', [
            'requerimientos' => $mine->map($this->present(...)),
            'activities' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
            ]),
            'canCreate' => $user->can('create', Requerimiento::class),
        ]);
    }

    public function store(Request $request, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('create', Requerimiento::class);

        $user = $request->user();
        $type = $request->validate(['type' => ['required', 'in:servicio,presupuesto,marketing,ti']])['type'];
        $isMarketing = $type === 'marketing';
        $isPresupuesto = $type === 'presupuesto';
        $usesGenericDetail = in_array($type, ['servicio', 'ti'], true);
        $format = $this->formatFor($type);

        $data = $request->validate([
            ...$this->validationRulesFor($type),
            'activity_id' => ['nullable', 'integer', Rule::exists('activities', 'id')],
        ]);

        if (! empty($data['activity_id'])) {
            $activity = Activity::with('planGroup.monthlyPlan')->find($data['activity_id']);

            if (! $activity || $activity->planGroup->monthlyPlan->area_id !== $user->area_id) {
                return back()->withErrors(['activity_id' => 'La actividad seleccionada no pertenece a tu área.']);
            }
        }

        $requerimiento = Requerimiento::create([
            'area_id' => $user->area_id,
            'user_id' => $user->id,
            'activity_id' => $data['activity_id'] ?? null,
            'type' => $type,
            'status' => RequerimientoStatus::Borrador,
            'routed_to' => RequerimientoType::from($type)->routedTo(),
            'detail' => $usesGenericDetail ? $data['detail'] : null,
            'especificaciones' => $usesGenericDetail ? ($data['especificaciones'] ?? null) : null,
            'requested_amount' => $isPresupuesto ? array_sum(array_column($data['items'], 'monto_solicitado')) : null,
            'needed_by' => $isMarketing ? ($data['needed_by'] ?? null) : null,
            'format_code' => $format['code'] ?? null,
            'format_version' => $format['version'] ?? null,
            'format_approved_at' => $format['approved_at'] ?? null,
        ]);

        if ($isMarketing) {
            $this->syncMaterials($requerimiento, $data['materials'], $request);
        } elseif ($isPresupuesto) {
            $this->syncBudgetItems($requerimiento, $data['items']);
        }

        $service->submit($requerimiento, $user);

        return back();
    }

    public function correct(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('correct', $requerimiento);

        $type = $requerimiento->type->value;
        $isMarketing = $type === 'marketing';
        $isPresupuesto = $type === 'presupuesto';

        $data = $request->validate($this->validationRulesFor($type));

        try {
            if ($isMarketing) {
                $service->correct($requerimiento, $request->user(), ['needed_by' => $data['needed_by'] ?? null]);
                $this->syncMaterials($requerimiento, $data['materials'], $request);
            } elseif ($isPresupuesto) {
                $service->correct($requerimiento, $request->user(), [
                    'requested_amount' => array_sum(array_column($data['items'], 'monto_solicitado')),
                ]);
                $this->syncBudgetItems($requerimiento, $data['items']);
            } else {
                $service->correct($requerimiento, $request->user(), $data);
            }
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    /**
     * @param  array<int, array{material: string, especificaciones?: ?string, publico_objetivo?: ?string}>  $materials
     */
    private function syncMaterials(Requerimiento $requerimiento, array $materials, Request $request): void
    {
        foreach ($requerimiento->materials as $existing) {
            if ($existing->image_path) {
                Storage::disk('local')->delete($existing->image_path);
            }
        }

        $requerimiento->materials()->delete();

        foreach ($materials as $index => $material) {
            $imagePath = null;
            $imageOriginalName = null;
            $file = $request->file("materials.$index.image");

            if ($file) {
                $imagePath = Storage::disk('local')->putFile('requerimientos/materiales', $file);
                $imageOriginalName = $file->getClientOriginalName();
            }

            $requerimiento->materials()->create([
                'material' => $material['material'],
                'especificaciones' => $material['especificaciones'] ?? null,
                'publico_objetivo' => $material['publico_objetivo'] ?? null,
                'image_path' => $imagePath,
                'image_original_name' => $imageOriginalName,
                'position' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array{objetivo: string, monto_solicitado: numeric-string|float, fecha_requerida?: ?string, especificacion_uso?: ?string}>  $items
     */
    private function syncBudgetItems(Requerimiento $requerimiento, array $items): void
    {
        $requerimiento->budgetItems()->delete();

        foreach ($items as $index => $item) {
            $requerimiento->budgetItems()->create([
                'objetivo' => $item['objetivo'],
                'monto_solicitado' => $item['monto_solicitado'],
                'fecha_requerida' => $item['fecha_requerida'] ?? null,
                'especificacion_uso' => $item['especificacion_uso'] ?? null,
                'position' => $index,
            ]);
        }
    }

    public function cancel(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('cancel', $requerimiento);

        try {
            $service->cancel($requerimiento, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function inbox(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->hasRole('gerencia') || $user->hasRole('marketing') || $user->hasRole('admin'), 403);

        $routedTo = match (true) {
            $user->hasRole('gerencia') => 'gerencia',
            $user->hasRole('marketing') => 'marketing',
            default => 'admin',
        };
        $status = $request->query('status', 'pendientes');
        $month = $request->query('month');

        $months = Requerimiento::where('routed_to', $routedTo)
            ->whereNotNull('submitted_at')
            ->get(['submitted_at'])
            ->map(fn ($r) => $r->submitted_at->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($ym) => [
                'value' => $ym,
                'label' => self::MESES[(int) substr($ym, 5, 2)].' '.substr($ym, 0, 4),
            ]);

        $query = Requerimiento::query()->where('routed_to', $routedTo)->with(self::RELATIONS);

        $query = match ($status) {
            'aprobados' => $query->where('status', RequerimientoStatus::Aprobado->value),
            'atendidos' => $query->where('status', RequerimientoStatus::Atendido->value),
            'rechazados' => $query->whereIn('status', [RequerimientoStatus::Rechazado->value, RequerimientoStatus::Anulado->value]),
            'todos' => $query,
            default => $query->whereIn('status', [RequerimientoStatus::Enviado->value, RequerimientoStatus::Corregido->value]),
        };

        if ($month) {
            [$year, $monthNumber] = explode('-', $month);
            $query->whereYear('submitted_at', $year)->whereMonth('submitted_at', $monthNumber);
        }

        return Inertia::render('Requerimientos/Inbox', [
            'items' => $query->orderByDesc('id')->get()->map($this->present(...)),
            'statusFilter' => $status,
            'routedTo' => $routedTo,
            'months' => $months,
            'monthFilter' => $month,
        ]);
    }

    public function approve(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('decide', $requerimiento);

        $data = $request->validate([
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $service->approve($requerimiento, $request->user(), $data['approved_amount'] ?? null, $data['comment'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    public function observe(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('decide', $requerimiento);

        $data = $request->validate(['comment' => ['required', 'string']]);

        try {
            $service->observe($requerimiento, $request->user(), $data['comment']);
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    public function reject(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('decide', $requerimiento);

        $data = $request->validate(['comment' => ['required', 'string']]);

        try {
            $service->reject($requerimiento, $request->user(), $data['comment']);
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    public function attend(Request $request, Requerimiento $requerimiento, RequerimientoWorkflowService $service): RedirectResponse
    {
        $this->authorize('decide', $requerimiento);

        $data = $request->validate(['comment' => ['nullable', 'string']]);

        try {
            $service->markAttended($requerimiento, $request->user(), $data['comment'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['requerimiento' => $e->getMessage()]);
        }

        return back();
    }

    public function downloadMaterialImage(RequerimientoMaterial $material): StreamedResponse
    {
        $this->authorize('view', $material->requerimiento);

        return Storage::disk('local')->response($material->image_path, $material->image_original_name);
    }

    private function present(Requerimiento $requerimiento): array
    {
        return [
            'id' => $requerimiento->id,
            'type' => $requerimiento->type->value,
            'status' => $requerimiento->status->value,
            'detail' => $requerimiento->detail,
            'especificaciones' => $requerimiento->especificaciones,
            'requested_amount' => $requerimiento->requested_amount,
            'approved_amount' => $requerimiento->approved_amount,
            'needed_by' => $requerimiento->needed_by?->toDateString(),
            'format_code' => $requerimiento->format_code,
            'format_version' => $requerimiento->format_version,
            'submitted_at' => $requerimiento->submitted_at,
            'decided_at' => $requerimiento->decided_at,
            'user' => $requerimiento->user?->only(['id', 'name', 'position']),
            'area' => $requerimiento->area?->only(['id', 'name']),
            'activity' => $requerimiento->activity?->only(['id', 'name']),
            'decided_by' => $requerimiento->decidedBy?->only(['id', 'name']),
            'materials' => $requerimiento->materials->map(fn ($m) => [
                'id' => $m->id,
                'material' => $m->material,
                'especificaciones' => $m->especificaciones,
                'publico_objetivo' => $m->publico_objetivo,
                'image_url' => $m->image_path ? route('requerimiento-materials.image', $m->id) : null,
                'image_name' => $m->image_original_name,
            ]),
            'items' => $requerimiento->budgetItems->map(fn ($i) => [
                'id' => $i->id,
                'objetivo' => $i->objetivo,
                'monto_solicitado' => $i->monto_solicitado,
                'fecha_requerida' => $i->fecha_requerida?->toDateString(),
                'especificacion_uso' => $i->especificacion_uso,
            ]),
            'logs' => $requerimiento->statusLogs->map(fn ($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'comment' => $log->comment,
                'performed_by' => $log->performedBy?->name,
                'created_at' => $log->created_at,
            ]),
        ];
    }
}
