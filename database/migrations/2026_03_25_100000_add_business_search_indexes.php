<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBusinessSearchIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: This migration requires permissions to create the `pg_trgm` extension.
     */
    public function up()
    {
        // Enable trigram extension for fast LIKE searches
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm;");

        // Trigram GIN indexes for fast case-insensitive substring search
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_name_trgm_idx ON businesses USING gin (LOWER(name) gin_trgm_ops);");
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_description_trgm_idx ON businesses USING gin (LOWER(COALESCE(description, '')) gin_trgm_ops);");

        // `keywords` is json in this schema; cast to jsonb for GIN indexing.
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_keywords_gin_idx ON businesses USING gin ((keywords::jsonb));");

        // Common filter/sort btree indexes
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_category_id_idx ON businesses (category_id);");
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_is_featured_idx ON businesses (is_featured);");
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_is_approved_idx ON businesses (is_approved);");
        DB::statement("CREATE INDEX IF NOT EXISTS businesses_created_at_idx ON businesses (created_at);");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("DROP INDEX IF EXISTS businesses_name_trgm_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_description_trgm_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_keywords_gin_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_category_id_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_is_featured_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_is_approved_idx;");
        DB::statement("DROP INDEX IF EXISTS businesses_created_at_idx;");

        // We do not drop the extension automatically to avoid removing a shared resource.
    }
}
