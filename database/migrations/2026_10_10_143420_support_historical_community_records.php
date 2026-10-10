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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->change();
            $table->string('source_key', 100)->nullable()->unique();
        });
        Schema::table('fun_games', function (Blueprint $table): void {
            $table->string('format', 20)->default('single');
            $table->boolean('match_records_complete')->default(true);
            $table->string('source_key', 100)->nullable()->unique();
        });
        Schema::table('match_logs', function (Blueprint $table): void {
            $table->unsignedTinyInteger('player1_score')->nullable()->default(0)->change();
            $table->unsignedTinyInteger('player2_score')->nullable()->default(0)->change();
            $table->string('source_key', 100)->nullable()->unique();
        });
        Schema::table('hc_histories', function (Blueprint $table): void {
            $table->string('source_key', 100)->nullable()->unique();
        });
        Schema::create('fun_game_podium', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fun_game_id')->constrained('fun_games')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unique(['fun_game_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->whereNull('phone')->exists()
            || DB::table('match_logs')->whereNull('player1_score')->orWhereNull('player2_score')->exists()
            || DB::table('fun_games')->whereNotNull('source_key')->exists()) {
            throw new RuntimeException('Rollback ditolak: profil tanpa nomor atau skor belum tercatat harus tetap dipertahankan.');
        }
        Schema::dropIfExists('fun_game_podium');
        Schema::table('hc_histories', fn (Blueprint $table) => $table->dropColumn('source_key'));
        Schema::table('match_logs', function (Blueprint $table): void {
            $table->dropColumn('source_key');
            $table->unsignedTinyInteger('player1_score')->nullable(false)->default(0)->change();
            $table->unsignedTinyInteger('player2_score')->nullable(false)->default(0)->change();
        });
        Schema::table('fun_games', fn (Blueprint $table) => $table->dropColumn(['format', 'match_records_complete', 'source_key']));
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('source_key');
            $table->string('phone')->nullable(false)->change();
        });
    }
};
