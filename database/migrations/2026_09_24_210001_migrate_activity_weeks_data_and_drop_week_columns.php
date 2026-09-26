<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('activities')->select('id', 'week_start', 'week_end')->get() as $activity) {
            $rows = [];

            for ($week = $activity->week_start; $week <= $activity->week_end; $week++) {
                $rows[] = [
                    'activity_id' => $activity->id,
                    'week_number' => $week,
                ];
            }

            if ($rows !== []) {
                DB::table('activity_weeks')->insert($rows);
            }
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['week_start', 'week_end']);
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedTinyInteger('week_start')->nullable()->after('name');
            $table->unsignedTinyInteger('week_end')->nullable()->after('week_start');
        });

        $bounds = DB::table('activity_weeks')
            ->selectRaw('activity_id, MIN(week_number) as week_start, MAX(week_number) as week_end')
            ->groupBy('activity_id')
            ->get();

        foreach ($bounds as $bound) {
            DB::table('activities')
                ->where('id', $bound->activity_id)
                ->update(['week_start' => $bound->week_start, 'week_end' => $bound->week_end]);
        }
    }
};
