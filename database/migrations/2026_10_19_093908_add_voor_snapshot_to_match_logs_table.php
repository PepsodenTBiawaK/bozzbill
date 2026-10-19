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
        Schema::table('match_logs', function (Blueprint $table): void {
            $table->string('voor_type', 30)->nullable();
            $table->foreignId('voor_recipient_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('match_logs')->whereNotNull('voor_type')->exists()) {
            throw new RuntimeException('Rollback ditolak: catatan voor pertandingan harus tetap dipertahankan.');
        }

        Schema::table('match_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voor_recipient_id');
            $table->dropColumn('voor_type');
        });
    }
};
