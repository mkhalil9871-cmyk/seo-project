<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('url_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('url_hash', 64); // sha256(normalized url), used for dedupe
            $table->unsignedInteger('depth')->default(0);
            $table->string('discovered_via', 16)->default('link'); // 'link' | 'sitemap'

            // pending -> processing -> done | failed | skipped
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('locked_at')->nullable(); // prevents double-processing if cron overlaps
            $table->timestamps();

            $table->unique(['audit_id', 'url_hash']);
            $table->index(['audit_id', 'status']);
            $table->index('locked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_queue');
    }
};
