<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();

            // Nullable on purpose: site-level issues (e.g. "sitemap URL never crawled")
            // aren't tied to a specific crawled page.
            $table->foreignId('crawled_page_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 64)->index();     // e.g. missing_title, thin_content, broken_link
            $table->string('severity', 16)->index();  // critical | high | medium | low
            $table->string('category', 32);           // technical | content | on_page
            $table->text('detail')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'severity']);
            $table->index(['audit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_issues');
    }
};
