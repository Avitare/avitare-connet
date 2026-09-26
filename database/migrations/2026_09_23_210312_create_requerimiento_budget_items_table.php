<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requerimiento_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_id')->constrained()->cascadeOnDelete();
            $table->string('objetivo');
            $table->decimal('monto_solicitado', 12, 2);
            $table->date('fecha_requerida')->nullable();
            $table->text('especificacion_uso')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('requerimiento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requerimiento_budget_items');
    }
};
