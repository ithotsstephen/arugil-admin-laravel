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
        // For PostgreSQL, convert text column to json with existing data
        \DB::statement('ALTER TABLE businesses ALTER COLUMN services TYPE json USING CASE WHEN services IS NULL THEN NULL ELSE (\'[]\')::json END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->text('services')->nullable()->change();
        });
    }
};
