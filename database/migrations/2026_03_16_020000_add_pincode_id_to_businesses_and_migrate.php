<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add nullable pincode_id column
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('pincode_id')->nullable()->after('pincode');
            $table->foreign('pincode_id')->references('id')->on('pincodes')->nullOnDelete();
        });

        // Migrate existing businesses' pincode strings into pincode records and set pincode_id
        $hasPincodeCol = Schema::hasColumn('businesses', 'pincode');
        $businesses = DB::table('businesses')->select('id', 'area_id')->get();

        foreach ($businesses as $b) {
            $code = null;
            if ($hasPincodeCol) {
                $code = DB::table('businesses')->where('id', $b->id)->value('pincode');
            }

            if (empty($code) && $b->area_id) {
                $code = DB::table('areas')->where('id', $b->area_id)->value('pincode');
            }

            if (empty($code)) {
                continue;
            }

            $p = DB::table('pincodes')->where('code', $code)->first();
            if (!$p) {
                $pId = DB::table('pincodes')->insertGetId([
                    'code' => $code,
                    'city_id' => null,
                    'district_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $pId = $p->id;
            }

            DB::table('businesses')->where('id', $b->id)->update(['pincode_id' => $pId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['pincode_id']);
            $table->dropColumn('pincode_id');
        });
    }
};
