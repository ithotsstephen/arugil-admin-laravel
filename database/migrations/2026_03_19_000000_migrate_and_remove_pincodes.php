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
        // If businesses have pincode_id, copy the related code into businesses.pincode
        if (Schema::hasTable('businesses') && Schema::hasTable('pincodes')) {
            if (Schema::hasColumn('businesses', 'pincode_id')) {
                $businesses = DB::table('businesses')->whereNotNull('pincode_id')->get();
                foreach ($businesses as $b) {
                    $code = DB::table('pincodes')->where('id', $b->pincode_id)->value('code');
                    if ($code) {
                        DB::table('businesses')->where('id', $b->id)->update(['pincode' => $code]);
                    }
                }

                // Drop foreign key and column
                Schema::table('businesses', function (Blueprint $table) {
                    if (Schema::hasColumn('businesses', 'pincode_id')) {
                        $table->dropForeign(['pincode_id']);
                        $table->dropColumn('pincode_id');
                    }
                });
            }
        }

        // Finally remove the pincodes table entirely
        if (Schema::hasTable('pincodes')) {
            Schema::dropIfExists('pincodes');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate pincodes table
        if (! Schema::hasTable('pincodes')) {
            Schema::create('pincodes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20);
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('district_id')->nullable();
                $table->timestamps();
            });
        }

        // Add pincode_id column back to businesses
        if (Schema::hasTable('businesses') && ! Schema::hasColumn('businesses', 'pincode_id')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->unsignedBigInteger('pincode_id')->nullable()->after('pincode');
                $table->foreign('pincode_id')->references('id')->on('pincodes')->nullOnDelete();
            });

            // For businesses that have a pincode string, create/find pincodes and set pincode_id
            $businesses = DB::table('businesses')->whereNotNull('pincode')->get();
            foreach ($businesses as $b) {
                $code = trim($b->pincode);
                if ($code === '') continue;

                $p = DB::table('pincodes')->where('code', $code)->first();
                if (! $p) {
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
    }
};
