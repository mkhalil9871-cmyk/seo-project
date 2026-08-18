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
    Schema::create('serp_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
        $table->integer('position')->nullable();
        $table->string('url')->nullable();
        $table->string('title')->nullable();
        $table->json('raw')->nullable();
        $table->timestamp('checked_at');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_results');
    }
};
