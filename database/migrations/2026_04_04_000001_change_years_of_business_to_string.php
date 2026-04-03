<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('businesses', 'years_of_business')) {
            return;
        }

        DB::statement('ALTER TABLE businesses ALTER COLUMN years_of_business TYPE VARCHAR(255) USING years_of_business::text');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('businesses', 'years_of_business')) {
            return;
        }

        DB::statement("ALTER TABLE businesses ALTER COLUMN years_of_business TYPE INTEGER USING NULLIF(REGEXP_REPLACE(years_of_business, '[^0-9-]', '', 'g'), '')::integer");
    }
};