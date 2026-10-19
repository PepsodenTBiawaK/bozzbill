<details class="rounded-2xl border border-[#333741] bg-[#16171a] p-3 sm:p-4">
    <summary class="min-h-11 flex items-center text-xs font-semibold text-[#dea538] cursor-pointer">Lihat Dasar Rekomendasi</summary>
    <div class="space-y-4 pt-2 text-xs text-[#d4d4d4]">
        @foreach ($data['reasons'] as $reason)
            <p>{{ $reason }}</p>
        @endforeach
        <p class="text-[#959595]">Naik: win rate minimal 65%; turun: maksimal 35%, disertai konsistensi pada minimal 3 dan setidaknya separuh event yang dinilai. Minimum 20 match, 4 event, dan 5 lawan berbeda. Performa empat event terbaru ikut diperiksa jika tersedia pembanding.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="text-left font-semibold text-white pb-2">Hasil menurut HC lawan saat match</caption>
                <thead class="text-[#959595]"><tr><th class="py-2 pr-3">HC Lawan</th><th class="py-2 pr-3">Menang / Match</th><th class="py-2">Win Rate</th></tr></thead>
                <tbody>
                    @foreach ($data['opponent_results'] as $group)
                        <tr class="border-t border-[#333741]"><td class="py-2 pr-3">{{ $group['handicap'] }}</td><td class="py-2 pr-3">{{ $group['wins'] }} / {{ $group['matches'] }}</td><td class="py-2">{{ $group['win_rate'] !== null ? $group['win_rate'].'%' : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="space-y-2">
            <p class="font-semibold text-white">Konsistensi per event</p>
            @forelse ($data['event_results'] as $event)
                <div class="flex flex-wrap justify-between gap-1 border-t border-[#333741] pt-2"><span>{{ $event['title'] }}</span><span class="text-[#959595]">{{ $event['wins'] }}/{{ $event['matches'] }} menang · {{ $event['win_rate'] }}%</span></div>
            @empty
                <p class="text-[#959595]">Belum ada event dengan data yang cukup lengkap pada HC saat ini.</p>
            @endforelse
        </div>
        <p class="text-[#959595]">Empat event terbaru: {{ $data['recent_win_rate'] !== null ? $data['recent_win_rate'].'%' : '—' }} · sebelumnya: {{ $data['previous_win_rate'] !== null ? $data['previous_win_rate'].'%' : '—' }}. Porsi lawan yang paling sering dihadapi: {{ $data['largest_opponent_share'] }}%.</p>
        <p class="text-[#959595]">{{ $data['other_hc_matches'] }} match dari HC/periode lain dipisahkan. {{ $data['unknown_voor_matches'] }} catatan tanpa informasi voor; {{ $data['unknown_race_matches'] }} tanpa race yang dapat dibaca. Hasil menang/kalah yang valid tetap dapat digunakan. Selisih skor dan kemenangan atas HC lebih tinggi tidak diberi bonus otomatis.</p>
    </div>
</details>
