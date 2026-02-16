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
            $table->string('about_title')->nullable()->after('description');
            $table->text('services')->nullable()->after('about_title');
            $table->string('email')->nullable()->after('whatsapp');
            $table->string('website')->nullable()->after('email');
            $table->string('facebook')->nullable()->after('website');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('twitter')->nullable()->after('instagram');
            $table->string('linkedin')->nullable()->after('twitter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'about_title',
                'services',
                'email',
                'website',
                'facebook',
                'instagram',
                'twitter',
                'linkedin',
            ]);
        });
    }
};
