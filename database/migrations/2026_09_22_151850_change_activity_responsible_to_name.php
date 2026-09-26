<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('responsible_name')->nullable()->after('name');
        });

        DB::statement('UPDATE activities SET responsible_name = (SELECT name FROM users WHERE users.id = activities.responsible_id)');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
            $table->dropColumn('responsible_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('responsible_id')->nullable()->after('name')->constrained('users')->restrictOnDelete();
        });

        DB::statement('UPDATE activities SET responsible_id = (SELECT id FROM users WHERE users.name = activities.responsible_name LIMIT 1)');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('responsible_name');
        });
    }
};
