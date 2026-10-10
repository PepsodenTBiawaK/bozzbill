<?php

namespace App\Console\Commands;

use App\Enums\Handicap;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

#[Signature('bozzbill:correct-hc-history {user} {event} {old} {new} {--by= : ID Superadmin pemberi koreksi} {--apply : Simpan koreksi; tanpa opsi ini hanya pratinjau}')]
#[Description('Mengoreksi waktu kenaikan HC dan snapshot match impor berdasarkan konfirmasi pengurus')]
class CorrectHandicapHistory extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::beginTransaction();
        try {
            $actor = User::query()->find($this->option('by'));
            $player = User::query()->lockForUpdate()->find($this->argument('user'));
            $event = FunGame::query()->find($this->argument('event'));
            $old = Handicap::tryFrom((string) $this->argument('old'));
            $new = Handicap::tryFrom((string) $this->argument('new'));
            if (! $actor?->isSuperadmin() || ! $player || ! $event?->isCompleted() || ! $event->source_key
                || ! $old || ! $new || $old === $new) {
                throw new RuntimeException('Periksa Superadmin, anggota, event arsip selesai, dan level HC yang berbeda.');
            }
            if ($player->handicap !== $new) {
                throw new RuntimeException('HC akun saat ini berbeda dari HC baru; koreksi perlu ditinjau agar perubahan terbaru tidak tertimpa.');
            }
            $changes = $player->hcHistories()->whereNotNull('old_hc')->lockForUpdate()->get();
            if ($changes->contains(fn (HcHistory $change): bool => $change->old_hc !== $old || $change->new_hc !== $new)
                || $changes->count() > 1) {
                throw new RuntimeException('Ada perubahan HC lain; timeline perlu ditinjau sebelum koreksi arsip.');
            }
            $effectiveAt = $event->event_date->copy()->endOfDay()->setMicrosecond(0);
            $history = $changes->first() ?? new HcHistory;
            $history->fill(['user_id' => $player->id, 'old_hc' => $old, 'new_hc' => $new,
                'approved_at' => $effectiveAt, 'changed_by' => $actor->id,
                'reason' => "Konfirmasi pengurus: HC {$old->value} menjadi {$new->value} setelah {$event->title}."]);
            if (! $history->exists) {
                $history->forceFill(['source_key' => "hc-confirmed:{$player->id}:{$event->id}"]);
            }
            $history->save();
            $affectedWinners = $player->allMatches()->whereNotNull('source_key')->pluck('winner_id')->unique();
            $matchesChanged = 0;
            foreach ([1, 2] as $side) {
                foreach (['<=' => $old, '>' => $new] as $operator => $handicap) {
                    $matchesChanged += MatchLog::query()->whereNotNull('source_key')
                        ->where('player'.$side.'_id', $player->id)
                        ->where('player'.$side.'_hc', '!=', $handicap->value)
                        ->whereHas('funGame', fn ($query) => $query->whereDate('event_date', $operator, $event->event_date->toDateString()))
                        ->update(['player'.$side.'_hc' => $handicap->value]);
                }
            }
            foreach (User::query()->whereIn('id', $affectedWinners)->orderBy('id')->lockForUpdate()->get() as $winner) {
                $winner->syncAchievementBadges(['giant_kills']);
            }
            if ($this->option('apply')) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->info($this->option('apply') ? 'Koreksi HC tersimpan.' : 'Pratinjau; tidak ada perubahan yang disimpan.');
            $this->line("{$player->name}: {$old->value} → {$new->value}, setelah {$event->title}; {$matchesChanged} snapshot match diselaraskan.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->error($exception instanceof RuntimeException ? $exception->getMessage() : 'Koreksi gagal; seluruh perubahan dibatalkan.');

            return self::FAILURE;
        }
    }
}
