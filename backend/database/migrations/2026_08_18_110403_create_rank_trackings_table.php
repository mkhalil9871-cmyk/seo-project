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
    Schema::create('rank_trackings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
        $table->string('domain');
        $table->unsignedInteger('position')->nullable(); // null = not found in results
        $table->string('search_engine')->default('google');
        $table->date('checked_at');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_trackings');
    }
};
