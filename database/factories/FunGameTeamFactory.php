<?php

namespace Database\Factories;

use App\Enums\FunGameStatus;
use App\Models\FunGame;
use App\Models\FunGameTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FunGameTeam>
 */
class FunGameTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fun_game_id' => fn () => FunGame::create([
                'title' => fake()->sentence(3),
                'event_date' => today(),
                'status' => FunGameStatus::Open,
                'format' => 'double',
            ])->id,
            'player1_id' => User::factory(),
            'player2_id' => User::factory(),
        ];
    }
}
