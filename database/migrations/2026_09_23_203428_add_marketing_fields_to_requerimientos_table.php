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
        Schema::table('requerimientos', function (Blueprint $table) {
            $table->date('needed_by')->nullable()->after('activity_id');
            $table->string('format_code')->nullable()->after('needed_by');
            $table->string('format_version')->nullable()->after('format_code');
            $table->date('format_approved_at')->nullable()->after('format_version');
            $table->text('detail')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requerimientos', function (Blueprint $table) {
            $table->dropColumn(['needed_by', 'format_code', 'format_version', 'format_approved_at']);
            $table->text('detail')->nullable(false)->change();
        });
    }
};
