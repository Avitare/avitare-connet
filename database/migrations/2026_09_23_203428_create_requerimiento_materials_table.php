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
        Schema::create('requerimiento_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_id')->constrained()->cascadeOnDelete();
            $table->string('material');
            $table->text('especificaciones')->nullable();
            $table->string('publico_objetivo')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_original_name')->nullable();
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
        Schema::dropIfExists('requerimiento_materials');
    }
};
