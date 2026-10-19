<section class="rounded-3xl border border-[#333741] bg-[#202227] p-5 sm:p-6">
    <h2 class="text-sm font-bold text-white">Belum Memenuhi Minimum Pertandingan</h2>
    <p class="text-xs text-[#959595] mt-1">Lengkapi minimal 10 match single dari 3 event selesai untuk masuk peringkat.</p>
    <div class="divide-y divide-[#333741] mt-3">
        @foreach ($players as $item)
            <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                <a href="{{ route('roster.show', $item['user']) }}" class="text-sm font-semibold text-white hover:text-[#dea538]">{{ $item['user']->name }}</a>
                <div class="text-xs text-right text-[#959595]">
                    <span class="font-semibold text-[#d4d4d4]">{{ $item['ranking_win_rate'] }}%</span>
                    <span> · {{ $item['ranking_wins'] }}/{{ $item['ranking_matches'] }} menang · {{ $item['ranking_events'] }}/3 event</span>
                    <p class="mt-1">Kurang {{ max(0, 10 - $item['ranking_matches']) }} match dan {{ max(0, 3 - $item['ranking_events']) }} event</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
