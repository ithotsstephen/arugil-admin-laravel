<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
            $table->index(['district_id', 'name']);
        });

        $cities = DB::table('cities')->select('id')->get();

        foreach ($cities as $city) {
            $districtIds = DB::table('areas')
                ->where('city_id', $city->id)
                ->whereNotNull('district_id')
                ->distinct()
                ->pluck('district_id');

            if ($districtIds->count() === 1) {
                DB::table('cities')
                    ->where('id', $city->id)
                    ->update(['district_id' => $districtIds->first()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropIndex(['district_id', 'name']);
            $table->dropColumn('district_id');
        });
    }
};