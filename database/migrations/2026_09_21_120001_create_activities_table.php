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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('week_start');
            $table->unsignedTinyInteger('week_end');
            $table->string('progress_type', 20);
            $table->decimal('numeric_goal_target', 10, 2)->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('deliverable')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('carried_over')->default(false);
            $table->foreignId('carried_over_from_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->boolean('added_after_approval')->default(false);
            $table->timestamps();

            $table->index(['plan_group_id', 'added_after_approval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
