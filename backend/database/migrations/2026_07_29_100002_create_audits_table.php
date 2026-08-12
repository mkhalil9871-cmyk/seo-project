<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // queued -> crawling -> scoring -> completed | failed | cancelled
            $table->string('status')->default('queued');

            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedInteger('pages_queued')->default(0);
            $table->unsignedInteger('pages_failed')->default(0);
            $table->unsignedInteger('sitemap_urls_found')->default(0);
            $table->unsignedInteger('sitemap_urls_uncrawled')->default(0);

            $table->decimal('overall_score', 5, 2)->nullable();
            $table->decimal('technical_score', 5, 2)->nullable();
            $table->decimal('content_score', 5, 2)->nullable();

            // Diff against the previous completed audit for the same project
            // ("12 new issues, 8 fixed") — cheap single read for a dashboard.
            $table->json('comparison_json')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
