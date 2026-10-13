<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fun_game_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fun_game_id')->constrained('fun_games')->cascadeOnDelete();
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['fun_game_id', 'player1_id']);
            $table->unique(['fun_game_id', 'player2_id']);
        });

        Schema::table('match_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('player1_id')->nullable()->change();
            $table->unsignedBigInteger('player2_id')->nullable()->change();
            $table->unsignedBigInteger('winner_id')->nullable()->change();
            $table->string('player1_hc', 10)->nullable()->change();
            $table->string('player2_hc', 10)->nullable()->change();
            $table->foreignId('team1_id')->nullable()->constrained('fun_game_teams')->cascadeOnDelete();
            $table->foreignId('team2_id')->nullable()->constrained('fun_game_teams')->cascadeOnDelete();
            $table->foreignId('winner_team_id')->nullable()->constrained('fun_game_teams')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('match_logs')->whereNull('player1_id')->orWhereNull('player2_id')->orWhereNull('winner_id')->exists()
            || DB::table('fun_game_teams')->exists()) {
            throw new RuntimeException('Rollback ditolak: pasangan dan pertandingan double harus tetap dipertahankan.');
        }

        Schema::table('match_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('winner_team_id');
            $table->dropConstrainedForeignId('team2_id');
            $table->dropConstrainedForeignId('team1_id');
            $table->unsignedBigInteger('player1_id')->nullable(false)->change();
            $table->unsignedBigInteger('player2_id')->nullable(false)->change();
            $table->unsignedBigInteger('winner_id')->nullable(false)->change();
            $table->string('player1_hc', 10)->nullable(false)->change();
            $table->string('player2_hc', 10)->nullable(false)->change();
        });

        Schema::dropIfExists('fun_game_teams');
    }
};
