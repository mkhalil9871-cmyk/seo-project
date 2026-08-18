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
    Schema::create('content_pieces', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();
        $table->foreignId('keyword_id')->nullable()->constrained()->nullOnDelete();
        $table->string('title')->nullable();
        $table->longText('body')->nullable();
        $table->string('status')->default('pending'); // pending|processing|done|failed
        $table->timestamp('generated_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pieces');
    }
};
