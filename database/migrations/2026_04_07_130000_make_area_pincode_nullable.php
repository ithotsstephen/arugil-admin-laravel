<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE areas ALTER COLUMN pincode DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE areas SET pincode = '' WHERE pincode IS NULL");
        DB::statement('ALTER TABLE areas ALTER COLUMN pincode SET NOT NULL');
    }
};