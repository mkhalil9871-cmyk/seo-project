<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->string('source_url', 2048);
            $table->string('target_url', 2048);
            $table->string('target_url_hash', 64);
            $table->string('anchor_text', 512)->nullable();
            $table->boolean('is_internal')->default(true);
            $table->boolean('is_nofollow')->default(false);
            $table->timestamps();

            $table->index(['audit_id', 'target_url_hash']);
            $table->index(['audit_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_links');
    }
};
