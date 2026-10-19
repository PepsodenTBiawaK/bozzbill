<?php

namespace Database\Factories;

use App\Enums\FunGameStatus;
use App\Enums\UserRole;
use App\Models\FunGame;
use App\Models\MatchLog;
use App\Models\MatchLogCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchLogCorrection>
 */
class MatchLogCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fun_game_id' => fn (): int => FunGame::create([
                'title' => fake()->sentence(3),
                'event_date' => today(),
                'status' => FunGameStatus::Completed,
            ])->id,
            'match_log_id' => function (array $attributes): int {
                $players = User::factory()->count(2)->create();

                return MatchLog::create([
                    'fun_game_id' => $attributes['fun_game_id'],
                    'player1_id' => $players[0]->id,
                    'player2_id' => $players[1]->id,
                    'player1_score' => 3,
                    'player2_score' => 1,
                    'winner_id' => $players[0]->id,
                    'player1_hc' => $players[0]->handicap,
                    'player2_hc' => $players[1]->handicap,
                    'stage' => 'Penyisihan',
                ])->id;
            },
            'changed_by' => User::factory()->state(['role' => UserRole::Superadmin]),
            'reason' => fake()->sentence(),
            'before_values' => function (array $attributes): array {
                $match = MatchLog::findOrFail($attributes['match_log_id']);

                return [...$match->correctionValues(), 'player1_score' => 1, 'player2_score' => 3, 'winner_id' => $match->player2_id];
            },
            'after_values' => fn (array $attributes): array => MatchLog::findOrFail($attributes['match_log_id'])->correctionValues(),
        ];
    }
}
