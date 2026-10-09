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
        Schema::create('match_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fun_game_id')->constrained('fun_games')->cascadeOnDelete();
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('player1_score')->default(0);
            $table->unsignedTinyInteger('player2_score')->default(0);
            $table->foreignId('winner_id')->constrained('users')->cascadeOnDelete();
            $table->string('player1_hc', 10);
            $table->string('player2_hc', 10);
            $table->string('stage', 50)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['player1_id', 'player2_id']);
            $table->index('fun_game_id');
            $table->index('winner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_logs');
    }
};
