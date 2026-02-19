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
        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            $table->index('city_id');
            $table->index('district_id');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropColumn('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('state_id');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn(['city_id', 'district_id']);
        });
    }
};
