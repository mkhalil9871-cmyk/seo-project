<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds crawler configuration to the existing projects table.
     * Existing columns (name, domain, industry, country, language, status)
     * are untouched — this only adds what the crawler needs.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sitemap_url', 2048)->nullable()->after('domain'); // auto-guessed if left blank
            $table->unsignedInteger('max_pages')->default(500)->after('status');
            $table->unsignedInteger('max_depth')->default(6)->after('max_pages');
            $table->unsignedInteger('crawl_delay_ms')->default(500)->after('max_depth');
            $table->boolean('respect_robots')->default(true)->after('crawl_delay_ms');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['sitemap_url', 'max_pages', 'max_depth', 'crawl_delay_ms', 'respect_robots']);
        });
    }
};
