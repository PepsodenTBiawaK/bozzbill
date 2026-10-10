<x-layouts.app>
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Dashboard Pengurus</h1>
            <p class="text-sm text-[#959595] mt-1">Pantau keanggotaan, event aktif, dan evaluasi handicap.</p>
        </div>

        <div>
            <div tabindex="0" role="region" aria-label="Ringkasan komunitas" class="overflow-x-auto pb-2 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#dea538]">
                <dl class="grid grid-cols-[repeat(5,minmax(8rem,1fr))] gap-2 sm:gap-3">
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4">
                        <dt class="text-[11px] font-medium text-[#959595] leading-tight min-h-7">Member Aktif</dt>
                        <dd class="text-xl sm:text-2xl font-bold text-[#dea538] mt-1 leading-none tabular-nums">{{ $stats['active_members'] }}</dd>
                    </div>
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4">
                        <dt class="text-[11px] font-medium text-[#959595] leading-tight min-h-7">Menunggu Aktivasi</dt>
                        <dd class="text-xl sm:text-2xl font-bold text-white mt-1 leading-none tabular-nums">{{ $stats['inactive_members'] }}</dd>
                    </div>
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4">
                        <dt class="text-[11px] font-medium text-[#959595] leading-tight min-h-7">Total Match</dt>
                        <dd class="text-xl sm:text-2xl font-bold text-white mt-1 leading-none tabular-nums">{{ $stats['total_matches'] }}</dd>
                    </div>
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4">
                        <dt class="text-[11px] font-medium text-[#959595] leading-tight min-h-7">Event Selesai</dt>
                        <dd class="text-xl sm:text-2xl font-bold text-white mt-1 leading-none tabular-nums">{{ $stats['completed_events'] }}</dd>
                    </div>
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4">
                        <dt class="text-[11px] font-medium text-[#959595] leading-tight min-h-7">Drilling Bulan Ini</dt>
                        <dd class="text-xl sm:text-2xl font-bold text-white mt-1 leading-none tabular-nums">{{ $stats['drills_this_month'] }}</dd>
                    </div>
                </dl>
            </div>
            <p class="min-[736px]:hidden text-[10px] text-[#888e9b] mt-1">Geser untuk melihat semua ringkasan</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-lg font-bold text-white">Fun Game Aktif</h2>
                    @if ($activeEvent)
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400">Berlangsung</span>
                    @endif
                </div>

                @if ($activeEvent)
                    <h3 class="text-xl font-bold text-white break-words">{{ $activeEvent->title }}</h3>
                    <p class="text-sm text-[#959595] mt-2">{{ $activeEvent->event_date->format('d M Y') }} &bull; {{ $activeEvent->matches_count }} match tercatat</p>
                    @if ($activeEvent->notes)
                        <p class="text-sm text-[#d4d4d4] leading-relaxed mt-3">{{ $activeEvent->notes }}</p>
                    @endif
                    <div class="flex flex-wrap items-center gap-3 mt-5">
                        <a href="{{ route('fun-games.quick-logger') }}" class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold transition">Catat Match</a>
                        <a href="{{ route('fun-games.show', $activeEvent) }}" class="px-4 py-2.5 rounded-xl bg-[#16171a] hover:bg-[#252830] border border-[#333741] text-sm font-semibold text-white transition">Rekap &amp; Tutup Event</a>
                    </div>
                @else
                    <p class="text-sm text-[#959595]">Belum ada event yang sedang berlangsung.</p>
                    <a href="{{ route('fun-games.index') }}" class="inline-flex items-center px-4 py-2.5 mt-5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold transition">Kelola Fun Game</a>
                @endif
            </section>

            <section class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6">
                <h2 class="text-lg font-bold text-white">Evaluasi Handicap</h2>
                <p class="text-xs text-[#959595] mt-1">Rekomendasi dari empat Fun Game terakhir.</p>
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <a href="{{ route('evaluating-hc.index', ['filter' => 'promote']) }}" class="rounded-2xl p-3 bg-emerald-500/5 border border-emerald-500/20 hover:border-emerald-500/50 transition">
                        <span class="block text-xs font-semibold text-emerald-400">Naik HC</span>
                        <span class="block text-2xl font-bold text-white mt-1">{{ $recommendationCounts['promote'] }}</span>
                    </a>
                    <a href="{{ route('evaluating-hc.index', ['filter' => 'demote']) }}" class="rounded-2xl p-3 bg-rose-500/5 border border-rose-500/20 hover:border-rose-500/50 transition">
                        <span class="block text-xs font-semibold text-rose-400">Turun HC</span>
                        <span class="block text-2xl font-bold text-white mt-1">{{ $recommendationCounts['demote'] }}</span>
                    </a>
                    <a href="{{ route('evaluating-hc.index', ['filter' => 'watch']) }}" class="rounded-2xl p-3 bg-amber-500/5 border border-amber-500/20 hover:border-amber-500/50 transition">
                        <span class="block text-xs font-semibold text-amber-400">Pantau</span>
                        <span class="block text-2xl font-bold text-white mt-1">{{ $recommendationCounts['watch'] }}</span>
                    </a>
                    <a href="{{ route('evaluating-hc.index', ['filter' => 'stay']) }}" class="rounded-2xl p-3 bg-[#16171a] border border-[#333741] hover:border-[#959595] transition">
                        <span class="block text-xs font-semibold text-[#959595]">Tetap</span>
                        <span class="block text-2xl font-bold text-white mt-1">{{ $recommendationCounts['stay'] }}</span>
                    </a>
                </div>
            </section>

            <section class="lg:col-span-2 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6">
                <h2 class="text-lg font-bold text-white mb-4">Input Match Terbaru</h2>
                <div class="space-y-3">
                    @forelse ($recentMatches as $match)
                        <div class="flex items-start justify-between gap-3 p-3.5 rounded-2xl bg-[#16171a] border border-[#2e313a]">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-white truncate">
                                    <span class="{{ $match->winner_id === $match->player1_id ? 'text-emerald-400' : 'text-white' }}">{{ $match->player1->name }}</span>
                                    <span class="text-xs font-normal text-[#888e9b]">vs</span>
                                    <span class="{{ $match->winner_id === $match->player2_id ? 'text-emerald-400' : 'text-white' }}">{{ $match->player2->name }}</span>
                                </p>
                                <p class="text-xs text-[#888e9b] mt-1 truncate">{{ $match->funGame->title }} &bull; {{ $match->stage ?: 'Penyisihan' }}</p>
                                <p class="text-xs text-[#959595] mt-1">
                                    Dicatat oleh {{ $match->recorder?->name ?? 'Pengurus' }}
                                    @if ($match->created_at)
                                        &bull; {{ $match->created_at->format('d M, H:i') }}
                                    @endif
                                </p>
                            </div>
                            <span class="text-base font-bold text-white shrink-0">{{ $match->player1_score }} - {{ $match->player2_score }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#959595]">Belum ada pertandingan yang dicatat.</p>
                    @endforelse
                </div>
            </section>

            <section class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-lg font-bold text-white">Handicap Anggota</h2>
                    <a href="{{ route('roster', ['tab' => 'roster']) }}" class="text-xs font-semibold text-[#dea538] hover:underline">Roster</a>
                </div>
                <dl class="divide-y divide-[#2e313a]">
                    @foreach ($handicapDistribution as $handicap => $count)
                        <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <dt class="text-sm font-semibold text-[#dea538]">HC {{ $handicap }}</dt>
                            <dd class="text-sm text-white">{{ $count }} <span class="text-xs text-[#959595]">pemain</span></dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    </div>
</x-layouts.app>
