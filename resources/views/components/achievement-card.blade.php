@props(['achievement'])
@php
    $badge = $achievement['badge'];
    $unlocked = $achievement['unlocked'];
@endphp
<article {{ $attributes->class(['rounded-2xl p-4 border bg-[#191a1e]', 'border-[#383838]' => $unlocked, 'border-[#2b2d35]' => ! $unlocked]) }}>
    <div class="flex items-start gap-3">
        <div class="size-10 rounded-xl flex items-center justify-center shrink-0 border {{ $unlocked ? 'bg-[#dea538]/10 text-[#dea538] border-[#dea538]/30' : 'bg-[#202227] text-[#777d89] border-[#333741]' }}">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                @switch($badge->icon)
                    @case('target')
                        <circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1" />
                        @break
                    @case('zap')
                        <path d="M13 2 4 14h7l-1 8 10-12h-7l1-8Z" />
                        @break
                    @case('trophy')
                        <path d="M8 3h8v5a4 4 0 0 1-8 0V3Zm0 1H4v2a4 4 0 0 0 4 4m8-6h4v2a4 4 0 0 1-4 4M12 12v6m-4 3h8m-7-3h6v3" />
                        @break
                    @case('swords')
                        <path d="m3 3 6 2 11 11-4 4L5 9 3 3Zm18 0-6 2-4 4m-2 2L4 16l4 4 5-5M3 21l3-3m12 0 3 3" />
                        @break
                    @default
                        <path d="M12 3c1 5 5 5 5 10a5 5 0 0 1-10 0c0-2 1-4 3-6 0 2 1 3 2 3V3Z" />
                @endswitch
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <h4 class="text-sm font-bold text-white">{{ $badge->name }}</h4>
            <span class="text-[10px] font-semibold {{ $unlocked ? 'text-emerald-400' : 'text-[#959595]' }}">{{ $unlocked ? 'Terbuka' : 'Terkunci' }}</span>
        </div>
    </div>
    <p class="text-xs text-[#959595] mt-3 leading-relaxed">{{ $badge->description }}</p>
    <div class="mt-3 space-y-1.5">
        <div class="flex items-center justify-between gap-2 text-[11px] text-[#959595]">
            <span>{{ $unlocked ? 'Target tercapai' : 'Progres tercatat' }}</span>
            <span class="font-semibold {{ $unlocked ? 'text-[#dea538]' : 'text-[#d4d4d4]' }}">{{ $achievement['current'] }}/{{ $achievement['target'] }} {{ $achievement['unit'] }}</span>
        </div>
        <div role="progressbar" aria-label="Progres {{ $badge->name }}" aria-valuemin="0" aria-valuemax="{{ $achievement['target'] }}" aria-valuenow="{{ $achievement['current'] }}" class="h-1 rounded-full overflow-hidden bg-[#30333b]">
            <div class="h-full rounded-full bg-[#dea538]" style="width: {{ $achievement['percentage'] }}%"></div>
        </div>
    </div>
</article>
