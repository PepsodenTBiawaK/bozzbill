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

        <!-- ================= TOP STATS ROW (4 CARDS) ================= -->
        <!-- Sesuai Figma Home node 180:434: Player Identity, Win Rate, Total Match, Menang vs HC Tinggi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 sm:gap-5">
            
            <!-- Card 1: Player Identity (lg:col-span-4) -->
            <div class="sm:col-span-2 lg:col-span-4 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-xl flex items-center justify-between">
                <!-- Watermark Logo Mark in Background -->
                <div class="absolute -right-6 -bottom-6 w-40 h-40 opacity-5 pointer-events-none">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="w-full h-full object-contain filter invert" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <!-- Avatar Circle -->
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#16171a] border-2 border-[#383838] flex items-center justify-center text-white font-bold text-lg sm:text-xl shrink-0 shadow-inner">
                        {{ $user->initials }}
                    </div>

                    <!-- Player Info -->
                    <div>
                        <p class="text-[10px] sm:text-xs uppercase tracking-widest font-semibold text-[#dea538]">
                            BOZZBILL PLAYER
                        </p>
                        <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight mt-0.5">
                            {{ $user->name }}
                        </h2>
                    </div>
                </div>

                <!-- Handicap Badge -->
                <div class="relative z-10 text-right">
                    <div class="inline-flex flex-col items-center">
                        <span class="text-lg sm:text-xl font-black text-[#dea538] tracking-wider">
                            HC {{ $user->handicap->value }}
                        </span>
                        <div class="w-full h-0.5 bg-[#dea538] rounded-full mt-0.5"></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Win Rate (lg:col-span-3) -->
            <div class="lg:col-span-3 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-10 -right-10 w-28 h-28 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-28 h-4 bg-[#fce2b5] transform rotate-45 translate-x-8 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-28 h-6 bg-[#dea538] transform rotate-45 translate-x-6 translate-y-5 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight">
                        {{ $stats['win_rate'] }} %
                    </p>
                    <p class="text-xs sm:text-sm font-semibold text-[#dea538] mt-2">
                        Win Rate
                    </p>
                </div>
            </div>

            <!-- Card 3: Total Match (lg:col-span-2) -->
            <div class="lg:col-span-2 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-10 -right-10 w-28 h-28 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-28 h-4 bg-[#fce2b5] transform rotate-45 translate-x-8 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-28 h-6 bg-[#dea538] transform rotate-45 translate-x-6 translate-y-5 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight">
                        {{ $stats['total_matches'] }}
                    </p>
                    <p class="text-xs sm:text-sm font-semibold text-[#dea538] mt-2">
                        Total Match
                    </p>
                </div>
            </div>

            <!-- Card 4: Menang vs HC Tinggi (lg:col-span-3) -->
            <div class="lg:col-span-3 bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <!-- Signature Corner Gold Ribbon -->
                <div class="absolute -top-10 -right-10 w-28 h-28 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-28 h-4 bg-[#fce2b5] transform rotate-45 translate-x-8 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-28 h-6 bg-[#dea538] transform rotate-45 translate-x-6 translate-y-5 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight">
                        {{ $stats['wins_vs_higher'] }}x
                    </p>
                    <p class="text-xs sm:text-sm font-semibold text-[#dea538] mt-2">
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

                    <a href="#all-events" class="text-xs text-[#dea538] hover:underline font-semibold flex items-center gap-1">
                        <span>Semua Event</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- 4 Cards Grid / Mobile Horizontal Scroll -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

                        <div class="h-32 rounded-2xl p-4 relative overflow-hidden shadow-md flex flex-col justify-between {{ $cardBg }}">
                            
                            <!-- Huge Watermark Placement Number (e.g. 1st, 2nd, 3rd) -->
                            @if ($watermarkText)
                                <div class="absolute -left-2 -top-3 text-7xl font-black italic opacity-20 pointer-events-none select-none tracking-tighter">
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
                                <h4 class="text-xl font-bold tracking-tight">
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

                <!-- Badges Grid (Sesuai Figma: Target, Zap, Trophy) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($allBadges as $badge)
                        @php
                            $isUnlocked = $badges->contains('id', $badge->id);
                        @endphp

                        <div class="rounded-2xl p-4 border transition-all duration-200 {{ $isUnlocked ? 'bg-[#191a1e] border-[#383838] shadow-md' : 'bg-[#191a1e]/40 border-[#2b2d35] opacity-40' }}">
                            <div class="flex items-start gap-3.5">
                                
                                <!-- Badge Icon -->
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 {{ $isUnlocked ? 'bg-[#dea538]/10 text-[#dea538] border border-[#dea538]/30 shadow-inner' : 'bg-[#202227] text-[#666666] border border-[#333741]' }}">
                                    @if ($badge->icon === 'target')
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="6" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="2" stroke-width="2"/>
                                        </svg>
                                    @elseif ($badge->icon === 'zap')
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    @elseif ($badge->icon === 'trophy')
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                        </svg>
                                    @elseif ($badge->icon === 'swords')
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                        </svg>
                                    @endif
                                </div>

                                <!-- Badge Content -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-sm font-bold text-white truncate">
                                            {{ $badge->name }}
                                        </h4>
                                        @if ($isUnlocked)
                                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                                Unlocked
                                            </span>
                                        @else
                                            <span class="text-[10px] text-[#666666]">
                                                Terkunci
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-[#959595] mt-1 leading-snug line-clamp-2">
                                        {{ $badge->description }}
                                    </p>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
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
                            $isWinner = $match->winner_id === $user->id;
                            $opponent = $match->player1_id === $user->id ? $match->player2 : $match->player1;
                            $userScore = $match->player1_id === $user->id ? $match->player1_score : $match->player2_score;
                            $oppScore = $match->player1_id === $user->id ? $match->player2_score : $match->player1_score;
                        @endphp

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-[#191a1e] border border-[#2f323a] flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $isWinner ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30' }}">
                                    {{ $isWinner ? 'W' : 'L' }}
                                </span>

                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-white truncate">
                                        vs {{ $opponent->name }} <span class="text-xs text-[#dea538] font-semibold">(HC {{ $opponent->handicap->value }})</span>
                                    </p>
                                    <p class="text-xs text-[#888e9b]">
                                        {{ $match->funGame->title }} &bull; {{ $match->stage ?? 'Fun Game Match' }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-base sm:text-lg font-black {{ $isWinner ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $userScore }} - {{ $oppScore }}
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
