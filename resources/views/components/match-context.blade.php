@props(['match'])
<p {{ $attributes->class(['text-[11px] text-[#959595] mt-1 break-words']) }}>
    {{ $match->inferredRace() !== null ? 'Race '.$match->inferredRace() : 'Race tidak diketahui' }}
    @unless ($match->isDouble())
        &bull; {{ $match->voorSummary() }}
    @endunless
</p>
