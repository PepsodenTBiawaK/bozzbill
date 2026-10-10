@props(['match', 'playerId' => null, 'separator' => '-'])

<span {{ $attributes->class(['text-xs font-medium whitespace-normal' => ! $match->hasRecordedScore()]) }}>{{ $match->scoreFor($playerId, $separator) }}</span>
