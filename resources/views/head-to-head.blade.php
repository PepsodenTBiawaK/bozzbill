<x-layouts.app>
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ================= GREETING / PAGE HEADER ================= -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                    Head to Head
                </h1>
                <p class="text-sm sm:text-base text-[#9ca3af] mt-1">
                    Cek statistik head to head dengan temanmu!
                </p>
            </div>

            <!-- Opponent Selector -->
            @if ($opponents->isNotEmpty())
                <div class="flex items-center gap-2 bg-[#202227] border border-[#333741] p-1.5 rounded-2xl shadow-md">
                    <label for="opponent_select" class="text-xs text-[#888e9b] font-medium px-2">Lawan:</label>
                    <select 
                        id="opponent_select"
                        onchange="window.location.href = '{{ route('head-to-head') }}?opponent_id=' + this.value"
                        class="bg-[#151619] border border-[#333741] text-white text-xs sm:text-sm font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-[#dea538] cursor-pointer"
                    >
                        @foreach ($opponents as $opp)
                            <option value="{{ $opp->id }}" {{ $selectedOpponent?->id === $opp->id ? 'selected' : '' }}>
                                {{ $opp->name }} (HC {{ $opp->handicap->value }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($selectedOpponent)
            <!-- ================= TOP CARD: DUEL COMPARISON (Figma Node 188:2730) ================= -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-10 relative overflow-hidden shadow-2xl">
                
                <!-- Watermark Logo Mark in Background -->
                <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none select-none">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="w-80 sm:w-96 h-auto object-contain filter invert" />
                </div>

                <div class="relative z-10">
                    
                    <!-- Players Row -->
                    <div class="grid grid-cols-12 items-center gap-4 sm:gap-6 mb-8 sm:mb-12">
                        
                        <!-- Player 1: Current User (Cols 1-5) -->
                        <div class="col-span-5 flex flex-col items-center text-center">
                            <!-- Avatar Circle with Gold Border -->
                            <div class="w-20 h-20 sm:w-28 sm:h-28 rounded-full bg-[#16171a] border-2 sm:border-3 border-[#dea538] flex items-center justify-center text-white font-black text-2xl sm:text-4xl shadow-xl shadow-black/40">
                                {{ $user->initials }}
                            </div>
                            
                            <!-- Label & Name -->
                            <p class="text-[10px] sm:text-xs uppercase tracking-widest font-semibold text-[#dea538] mt-3 sm:mt-4">
                                BOZZBILL PLAYER
                            </p>
                            <h2 class="text-base sm:text-2xl font-bold text-white tracking-tight mt-0.5">
                                {{ $user->name }}
                            </h2>
                            <p class="text-xs sm:text-sm font-semibold text-[#9ca3af] mt-0.5">
                                HC {{ $user->handicap->value }}
                            </p>
                        </div>

                        <!-- Center: VS Badge & Match Count (Cols 6-7) -->
                        <div class="col-span-2 flex flex-col items-center text-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-[#2a2418] border-2 border-[#dea538] flex items-center justify-center text-[#dea538] font-black text-sm sm:text-lg shadow-lg">
                                VS
                            </div>
                            <span class="text-xs sm:text-sm font-bold text-white tracking-wide mt-2">
                                {{ $h2h['total_matches'] }} Match
                            </span>
                        </div>

                        <!-- Player 2: Opponent (Cols 8-12) -->
                        <div class="col-span-5 flex flex-col items-center text-center">
                            <!-- Avatar Circle with Gold Border -->
                            <div class="w-20 h-20 sm:w-28 sm:h-28 rounded-full bg-[#16171a] border-2 sm:border-3 border-[#dea538] flex items-center justify-center text-white font-black text-2xl sm:text-4xl shadow-xl shadow-black/40">
                                {{ $selectedOpponent->initials }}
                            </div>
                            
                            <!-- Label & Name -->
                            <p class="text-[10px] sm:text-xs uppercase tracking-widest font-semibold text-[#dea538] mt-3 sm:mt-4">
                                BOZZBILL PLAYER
                            </p>
                            <h2 class="text-base sm:text-2xl font-bold text-white tracking-tight mt-0.5">
                                {{ $selectedOpponent->name }}
                            </h2>
                            <p class="text-xs sm:text-sm font-semibold text-[#9ca3af] mt-0.5">
                                HC {{ $selectedOpponent->handicap->value }}
                            </p>
                        </div>

                    </div>

                    <!-- Win Counts Row -->
                    <div class="flex items-end justify-between px-2 sm:px-6 mb-2">
                        <!-- User Win Count -->
                        <div>
                            <div class="flex items-baseline gap-1.5 sm:gap-2">
                                <span class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight">
                                    {{ $h2h['user_wins'] }}
                                </span>
                                <span class="text-sm sm:text-xl font-bold text-[#d4d4d4]">
                                    Menang
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm font-semibold text-[#9ca3af] mt-1">
                                {{ $h2h['user_percentage'] }}%
                            </p>
                        </div>

                        <!-- Opponent Win Count -->
                        <div class="text-right">
                            <div class="flex items-baseline justify-end gap-1.5 sm:gap-2">
                                <span class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#dea538] tracking-tight">
                                    {{ $h2h['opponent_wins'] }}
                                </span>
                                <span class="text-sm sm:text-xl font-bold text-[#dea538]">
                                    Menang
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm font-semibold text-[#dea538] mt-1">
                                {{ $h2h['opponent_percentage'] }}%
                            </p>
                        </div>
                    </div>

                    <!-- Win Distribution Bi-Color Bar -->
                    <div class="w-full h-3 sm:h-4 bg-[#141518] rounded-full overflow-hidden flex p-0.5 border border-[#333741]">
                        @if ($h2h['total_matches'] > 0)
                            <div 
                                class="h-full bg-gradient-to-r from-[#888e9b] to-[#b3b9c5] rounded-l-full transition-all duration-500" 
                                style="width: {{ $h2h['user_percentage'] }}%"
                            ></div>
                            <div 
                                class="h-full bg-gradient-to-r from-[#bc852c] to-[#dea538] rounded-r-full transition-all duration-500" 
                                style="width: {{ $h2h['opponent_percentage'] }}%"
                            ></div>
                        @else
                            <div class="h-full w-full bg-[#2a2c33] rounded-full"></div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- ================= BOTTOM CARD: RIWAYAT PERTEMUAN (Figma Node 188:2787) ================= -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            Riwayat Pertemuan
                        </h3>
                        <p class="text-xs text-[#959595] mt-0.5">
                            Daftar seluruh match resmi antara {{ $user->name }} dan {{ $selectedOpponent->name }}
                        </p>
                    </div>

                    <span class="text-xs text-[#dea538] font-semibold px-3 py-1 rounded-full bg-[#dea538]/10 border border-[#dea538]/20">
                        {{ $h2h['matches']->count() }} Match Tercatat
                    </span>
                </div>

                <!-- Match List -->
                <div class="space-y-3">
                    @forelse ($h2h['matches'] as $match)
                        @php
                            $isUserWinner = $match->winner_id === $user->id;
                            $userScore = $match->player1_id === $user->id ? $match->player1_score : $match->player2_score;
                            $oppScore = $match->player1_id === $user->id ? $match->player2_score : $match->player1_score;
                            $winnerName = $match->winner?->name ?? 'Pemenang';
                        @endphp

                        <div class="p-4 sm:p-5 rounded-2xl bg-[#191a1e] border border-[#2f323a] hover:border-[#424754] transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
                            
                            <!-- Left: Event Name & Date -->
                            <div class="min-w-0">
                                <h4 class="text-base font-bold text-white tracking-tight truncate">
                                    {{ $match->funGame?->title ?? 'Fun Game Match' }}
                                </h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-[#888e9b]">
                                        {{ $match->created_at->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="text-[#888e9b] text-xs">&bull;</span>
                                    <span class="text-xs text-[#dea538] font-semibold">
                                        {{ $match->stage ?? 'Babak Penyisihan' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Winner Tag Pill & Final Score -->
                            <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#2b2e38]">
                                
                                <!-- Winner Tag -->
                                <div class="px-3 py-1 rounded-xl bg-[#232018] border border-[#dea538]/30 text-[#dea538] text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ $winnerName }} menang</span>
                                </div>

                                <!-- Big Final Score (e.g. 4 : 3) -->
                                <div class="flex items-center gap-1.5 text-xl sm:text-2xl font-black tracking-tight">
                                    <span class="{{ $isUserWinner ? 'text-white' : 'text-[#888e9b]' }}">
                                        {{ $userScore }}
                                    </span>
                                    <span class="text-[#888e9b]">:</span>
                                    <span class="{{ ! $isUserWinner ? 'text-[#dea538]' : 'text-[#888e9b]' }}">
                                        {{ $oppScore }}
                                    </span>
                                </div>

                            </div>

                        </div>
                    @empty
                        <div class="py-12 text-center bg-[#191a1e] rounded-2xl border border-[#2f323a]">
                            <svg class="w-12 h-12 text-[#555a66] mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm font-semibold text-white">Belum Ada Riwayat Pertandingan</p>
                            <p class="text-xs text-[#888e9b] mt-1">
                                Anda dan {{ $selectedOpponent->name }} belum pernah bertemu dalam pertandingan Fun Game resmi.
                            </p>
                        </div>
                    @endforelse
                </div>

            </div>
        @else
            <div class="p-8 text-center bg-[#202227] rounded-3xl border border-[#333741]">
                <p class="text-sm text-[#888e9b]">Belum ada member lain yang terdaftar di komunitas.</p>
            </div>
        @endif

    </div>
</x-layouts.app>
