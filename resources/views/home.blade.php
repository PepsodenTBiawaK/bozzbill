<x-layouts.app>
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ================= GREETING HEADER ================= -->
        <div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                Halo, {{ $user->name }}
            </h1>
            <p class="text-sm sm:text-base text-[#9ca3af] mt-1">
                Cek performa billiardmu! Lihat seberapa baik permainanmu!
            </p>
        </div>

        <!-- ================= PLAYER IDENTITY & COMPACT STATS ================= -->
        <div class="grid grid-cols-3 lg:grid-cols-12 gap-2 sm:gap-3">
            
            <!-- Player Identity -->
            <div class="col-span-3 lg:col-span-5 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex items-center justify-between gap-3">
                <!-- Watermark Logo Mark in Background -->
                <div class="absolute -right-4 -bottom-4 w-24 h-24 opacity-5 pointer-events-none">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="w-full h-full object-contain" />
                </div>

                <div class="flex items-center gap-3 relative z-10 min-w-0">
                    <!-- Avatar Circle -->
                    <x-player-avatar :player="$user" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-[#16171a] border-2 border-[#383838] text-white font-bold text-sm sm:text-base shadow-inner" />

                    <!-- Player Info -->
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs uppercase tracking-wider font-semibold text-[#dea538] truncate">
                            BOZZBILL PLAYER
                        </p>
                        <h2 title="{{ $user->name }}" class="text-sm sm:text-base font-bold text-white tracking-tight mt-0.5 truncate">
                            {{ $user->name }}
                        </h2>
                    </div>
                </div>

                <!-- Handicap Badge -->
                <div class="relative z-10 text-right shrink-0">
                    <div class="inline-flex flex-col items-center">
                        <span class="text-sm sm:text-base font-black text-[#dea538] tracking-wider whitespace-nowrap">
                            HC {{ $user->handicap->value }}
                        </span>
                        <div class="w-full h-0.5 bg-[#dea538] rounded-full mt-0.5"></div>
                    </div>
                </div>
            </div>

            <!-- Win Rate -->
            <div class="lg:col-span-2 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $stats['win_rate'] }} %
                    </p>
                    <p class="text-[10px] sm:text-xs font-semibold text-[#dea538] mt-1 leading-tight">
                        Win Rate Single
                    </p>
                </div>
            </div>

            <!-- Total Match -->
            <div class="lg:col-span-2 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $stats['total_matches'] }}
                    </p>
                    <p class="text-[10px] sm:text-xs font-semibold text-[#dea538] mt-1 leading-tight">
                        Match Single
                    </p>
                </div>
            </div>

            <!-- Menang vs HC Tinggi -->
            <div class="lg:col-span-3 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $stats['wins_vs_higher'] }}x
                    </p>
                    <p class="text-[10px] sm:text-xs font-semibold text-[#dea538] mt-1 leading-tight">
                        Menang vs HC Tinggi
                    </p>
                </div>
            </div>

        </div>

        <!-- ================= MAIN BODY CONTAINER (Persis Figma Frame 5: 188:2321) ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-8 space-y-8 shadow-xl">
            
            <!-- SECTION 1: TREN 4 FUN GAME TERAKHIR -->
            <div>
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            Tren 4 Fun Game Terakhir
                        </h3>
                        <p class="text-xs text-[#959595] mt-0.5">
                            Parameter utama evaluasi Handicap (HC) berkala
                        </p>
                    </div>

                    <a href="{{ route('fun-games.index') }}" class="text-xs text-[#dea538] hover:underline font-semibold flex items-center gap-1">
                        <span>Semua Event</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- 4 Cards Grid / Mobile Horizontal Scroll -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
                    @forelse ($recentFunGames as $fg)
                        @php
                            // Styling variants matching Figma mock
                            $cardBg = match ($fg['badge_style']) {
                                'gold' => 'bg-gradient-to-br from-[#bc852c] to-[#996b1e] border-l-4 border-[#facf80] text-white',
                                'silver' => 'bg-gradient-to-br from-[#4b515d] to-[#363a43] border-l-4 border-[#9aa0a6] text-white',
                                'bronze' => 'bg-gradient-to-br from-[#7a4e1d] to-[#593712] border-l-4 border-[#c88d4b] text-white',
                                default => 'bg-[#191a1e] border border-[#2f323a] text-white',
                            };
                            $watermarkText = $fg['placement'] ?? '';
                        @endphp

                        <div class="h-24 rounded-xl p-3 relative overflow-hidden shadow-md flex flex-col justify-between {{ $cardBg }}">
                            
                            <!-- Huge Watermark Placement Number (e.g. 1st, 2nd, 3rd) -->
                            @if ($watermarkText)
                                <div class="absolute -left-1 -top-2 text-5xl font-black italic opacity-20 pointer-events-none select-none tracking-tighter">
                                    {{ $watermarkText }}
                                </div>
                            @endif

                            <!-- Top Header: Status Indicator & Date -->
                            <div class="flex items-center justify-between relative z-10">
                                <div class="flex items-center gap-1.5">
                                    @if ($fg['badge_style'] === 'gold')
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#fce2b5] shadow-sm"></span>
                                    @elseif ($fg['badge_style'] === 'silver')
                                        <span class="w-2.5 h-2.5 rounded-full bg-white shadow-sm"></span>
                                    @elseif ($fg['badge_style'] === 'bronze')
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#facf80] shadow-sm"></span>
                                    @else
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-semibold opacity-80">
                                    {{ $fg['event_date'] }}
                                </span>
                            </div>

                            <!-- Bottom Content: Event Name & Outcome -->
                            <div class="relative z-10 text-center">
                                <h4 title="{{ $fg['title'] }}" class="text-sm sm:text-base font-bold tracking-tight truncate">
                                    {{ $fg['short_title'] }}
                                </h4>
                                <p class="text-xs font-medium opacity-90 mt-0.5">
                                    {{ $fg['label'] }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center bg-[#191a1e] rounded-2xl border border-[#2f323a]">
                            <p class="text-sm text-[#959595]">Belum ada event Fun Game yang terselesaikan.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- SECTION 2: LENCANA PRESTASI (ACHIEVEMENT BADGES) -->
            <div>
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            Lencana Prestasi
                        </h3>
                        <p class="text-xs text-[#959595] mt-0.5">
                            Apresiasi pencapaian dan konsistensi member di meja biliar
                        </p>
                    </div>

                    <span class="text-xs text-[#dea538] font-bold px-2.5 py-1 rounded-full bg-[#dea538]/10 border border-[#dea538]/20">
                        {{ $badges->count() }} Terbuka
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($achievements as $achievement)
                        <x-achievement-card :achievement="$achievement" />
                    @endforeach
                </div>
                <a href="{{ route('achievements') }}" class="inline-flex items-center gap-1.5 min-h-11 mt-2 text-xs font-semibold text-[#dea538] hover:underline">
                    Lihat detail lencana &rarr;
                </a>
            </div>

            <!-- SECTION 3: REKAP MATCH TERBARU -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            Pertandingan Terbaru
                        </h3>
                        <p class="text-xs text-[#959595] mt-0.5">
                            Histori match di event Fun Game Komunitas Bozz
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse ($recentMatches as $match)
                        @php
                            $isWinner = $match->wonBy($user->id);
                            $opponent = $match->player1_id === $user->id ? $match->player2 : $match->player1;
                        @endphp

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-[#191a1e] border border-[#2f323a] flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $isWinner ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30' }}">
                                    {{ $isWinner ? 'W' : 'L' }}
                                </span>

                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-white {{ $match->isDouble() ? 'line-clamp-2' : 'truncate' }}">
                                        @if ($match->isDouble())
                                            {{ $match->sideName(1) }} vs {{ $match->sideName(2) }}
                                        @else
                                            vs {{ $opponent->name }} <span class="text-xs text-[#dea538] font-semibold">(HC {{ $opponent->handicap->value }})</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-[#888e9b]">
                                        {{ $match->funGame->title }} &bull; {{ $match->isDouble() ? 'Double · ' : '' }}{{ $match->stage ?? 'Fun Game Match' }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-base sm:text-lg font-black {{ $isWinner ? 'text-emerald-400' : 'text-rose-400' }}">
                                    <x-match-score :match="$match" :player-id="$user->id" />
                                </span>
                                <p class="text-[10px] text-[#888e9b] uppercase tracking-wider font-semibold">
                                    {{ $isWinner ? 'Menang' : 'Kalah' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-[#959595]">
                            Belum ada riwayat pertandingan.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-layouts.app>
