@props(['teams', 'name', 'label', 'value' => '', 'required' => false, 'exclude' => '[]', 'allowed' => 'null'])
@php
    $options = $teams->map(fn ($team) => [
        'id' => (string) $team->id,
        'name' => $team->displayName(),
        'handicap' => $team->player1->handicap->value.' / '.$team->player2->handicap->value,
    ])->values()->all();
@endphp
<x-player-picker :players="$teams" :options="$options" kind="team" :name="$name" :label="$label" :value="$value" :required="$required" :exclude="$exclude" :allowed="$allowed" {{ $attributes }} />
