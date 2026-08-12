<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawled_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('url_hash', 64);
            $table->unsignedInteger('depth')->default(0);
            $table->string('discovered_via', 16)->default('link'); // 'link' | 'sitemap'

            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('redirect_to', 2048)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('html_size_bytes')->nullable();
            $table->string('content_type', 255)->nullable();
            $table->string('x_robots_tag', 255)->nullable();

            // Parsed on-page data
            $table->string('title', 512)->nullable();
            $table->char('title_hash', 64)->nullable()->index(); // for fast duplicate-title grouping
            $table->string('meta_description', 1024)->nullable();
            $table->char('meta_description_hash', 64)->nullable()->index();
            $table->string('meta_robots', 255)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            // 'self' | 'points_elsewhere' | 'cross_domain' | 'points_to_non_200' | null
            $table->string('canonical_status', 32)->nullable();
            $table->string('charset', 64)->nullable();
            $table->string('lang', 32)->nullable();
            $table->boolean('has_viewport')->default(false);
            $table->json('headings')->nullable(); // {"h1":["..."],"h2":["..."]...}
            $table->unsignedInteger('word_count')->default(0);
            $table->char('content_hash', 64)->nullable()->index(); // duplicate-content detection

            $table->unsignedInteger('internal_link_count')->default(0);
            $table->unsignedInteger('external_link_count')->default(0);
            $table->unsignedInteger('inbound_internal_links')->default(0);

            $table->unsignedInteger('image_count')->default(0);
            $table->unsignedInteger('images_missing_alt')->default(0);

            $table->json('json_ld')->nullable();
            $table->boolean('has_schema')->default(false);
            $table->json('hreflangs')->nullable();
            $table->string('rel_next', 2048)->nullable();
            $table->string('rel_prev', 2048)->nullable();

            $table->boolean('is_https')->default(false);
            $table->boolean('has_mixed_content')->default(false);
            $table->boolean('is_indexable')->default(true);

            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['audit_id', 'url_hash']);
            $table->index(['audit_id', 'status_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawled_pages');
    }
};
