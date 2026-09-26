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
        Schema::table('activities', function (Blueprint $table) {
            $table->enum('deliverable_type', ['file', 'link'])->nullable()->after('deliverable');
            $table->string('deliverable_path')->nullable()->after('deliverable_type');
            $table->string('deliverable_original_name')->nullable()->after('deliverable_path');
            $table->string('deliverable_mime_type')->nullable()->after('deliverable_original_name');
            $table->unsignedInteger('deliverable_size')->nullable()->after('deliverable_mime_type');
            $table->string('deliverable_url')->nullable()->after('deliverable_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'deliverable_type',
                'deliverable_path',
                'deliverable_original_name',
                'deliverable_mime_type',
                'deliverable_size',
                'deliverable_url',
            ]);
        });
    }
};
