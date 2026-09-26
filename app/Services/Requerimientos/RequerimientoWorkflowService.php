<?php

namespace App\Services\Requerimientos;

use App\Enums\RequerimientoStatus;
use App\Models\Requerimiento;
use App\Models\RequerimientoStatusLog;
use App\Models\User;
use Carbon\Carbon;
use DomainException;

/**
 * Flujo (spec sección 3, igual para los 3 formatos):
 * borrador → enviado → (observado ↔ corregido) → aprobado → atendido; salidas: rechazado / anulado.
 * Cada transición queda registrada en RequerimientoStatusLog (usuario+fecha+comentario, reemplaza firmas en papel).
 */
class RequerimientoWorkflowService
{
    public function submit(Requerimiento $requerimiento, User $by): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Borrador]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Enviado;
        $requerimiento->submitted_at = Carbon::now();
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Enviado, $by);

        return $requerimiento->fresh();
    }

    public function observe(Requerimiento $requerimiento, User $by, string $comment): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Enviado, RequerimientoStatus::Corregido]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Observado;
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Observado, $by, $comment);

        return $requerimiento->fresh();
    }

    /**
     * El creador corrige el detalle/monto observado y lo reenvía a la bandeja del aprobador.
     */
    public function correct(Requerimiento $requerimiento, User $by, array $data): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Observado]);

        $from = $requerimiento->status;
        $requerimiento->fill($data);
        $requerimiento->status = RequerimientoStatus::Corregido;
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Corregido, $by);

        return $requerimiento->fresh();
    }

    public function approve(Requerimiento $requerimiento, User $by, ?float $approvedAmount, ?string $comment): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Enviado, RequerimientoStatus::Corregido]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Aprobado;
        $requerimiento->approved_amount = $approvedAmount;
        $requerimiento->decided_by = $by->id;
        $requerimiento->decided_at = Carbon::now();
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Aprobado, $by, $comment);

        return $requerimiento->fresh();
    }

    public function reject(Requerimiento $requerimiento, User $by, string $comment): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Enviado, RequerimientoStatus::Corregido]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Rechazado;
        $requerimiento->decided_by = $by->id;
        $requerimiento->decided_at = Carbon::now();
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Rechazado, $by, $comment);

        return $requerimiento->fresh();
    }

    public function markAttended(Requerimiento $requerimiento, User $by, ?string $comment): Requerimiento
    {
        $this->assertStatus($requerimiento, [RequerimientoStatus::Aprobado]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Atendido;
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Atendido, $by, $comment);

        return $requerimiento->fresh();
    }

    /**
     * El creador anula su propio requerimiento mientras todavía no hay una decisión final.
     */
    public function cancel(Requerimiento $requerimiento, User $by): Requerimiento
    {
        $this->assertStatus($requerimiento, [
            RequerimientoStatus::Borrador,
            RequerimientoStatus::Enviado,
            RequerimientoStatus::Observado,
            RequerimientoStatus::Corregido,
        ]);

        $from = $requerimiento->status;
        $requerimiento->status = RequerimientoStatus::Anulado;
        $requerimiento->save();

        $this->log($requerimiento, $from, RequerimientoStatus::Anulado, $by);

        return $requerimiento->fresh();
    }

    /**
     * @param  RequerimientoStatus[]  $allowed
     */
    private function assertStatus(Requerimiento $requerimiento, array $allowed): void
    {
        if (! in_array($requerimiento->status, $allowed, true)) {
            throw new DomainException('Este requerimiento no está en un estado válido para esa acción.');
        }
    }

    private function log(
        Requerimiento $requerimiento,
        RequerimientoStatus $from,
        RequerimientoStatus $to,
        User $by,
        ?string $comment = null,
    ): void {
        RequerimientoStatusLog::create([
            'requerimiento_id' => $requerimiento->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'performed_by' => $by->id,
            'comment' => $comment,
        ]);
    }
}
