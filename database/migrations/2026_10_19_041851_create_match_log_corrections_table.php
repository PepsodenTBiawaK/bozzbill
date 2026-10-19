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
        Schema::create('match_log_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fun_game_id')->constrained('fun_games')->cascadeOnDelete();
            $table->foreignId('match_log_id')->constrained('match_logs')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->json('before_values');
            $table->json('after_values');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('match_log_corrections')->exists()) {
            throw new RuntimeException('Rollback ditolak: riwayat koreksi match harus tetap dipertahankan.');
        }

        Schema::dropIfExists('match_log_corrections');
    }
};
