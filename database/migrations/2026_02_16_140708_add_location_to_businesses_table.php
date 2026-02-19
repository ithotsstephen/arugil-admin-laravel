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
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            
            $table->index('state_id');
            $table->index('city_id');
            $table->index('district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn(['state_id', 'city_id', 'district_id']);
        });
    }
};
