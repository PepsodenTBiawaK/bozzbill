<?php

namespace App\Console\Commands;

use App\Models\User;
use App\WinRateRanking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bozzbill:evaluate-hc {--json : Tampilkan simulasi dalam JSON}')]
#[Description('Simulasi rekomendasi HC berdasarkan histori, tanpa mengubah akun atau keputusan')]
class EvaluateHandicaps extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $matches = WinRateRanking::completedSingles()->with('funGame')->get();
        $firstSides = $matches->groupBy('player1_id');
        $secondSides = $matches->groupBy('player2_id');
        $rows = User::query()->with('hcHistories')->orderBy('name')->get()->map(function (User $user) use ($firstSides, $secondSides): array {
            $result = $user->evaluateHandicap($firstSides->get($user->id, collect())->concat($secondSides->get($user->id, collect())));

            return ['id' => $user->id, 'name' => $user->name, 'handicap' => $user->handicap->value,
                'recommendation' => $result['recommendation']->value, 'suggested_hc' => $result['suggested_hc']->value,
                'matches' => $result['total_matches'], 'events' => $result['events_count'], 'opponents' => $result['opponents_count'],
                'win_rate' => $result['win_rate'], 'excluded' => $result['excluded_matches'], 'evidence' => $result['evidence']];
        });
        if ($this->option('json')) {
            $this->line($rows->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->table(['Nama', 'HC', 'Saran', 'Match', 'Event', 'Lawan', 'WR'], $rows->map(fn (array $row): array => [$row['name'], $row['handicap'], $row['recommendation'], $row['matches'], $row['events'], $row['opponents'], $row['win_rate'].'%'])->all());
            $this->info('Simulasi selesai. Tidak ada HC atau data pertandingan yang diubah.');
        }

        return self::SUCCESS;
    }
}
