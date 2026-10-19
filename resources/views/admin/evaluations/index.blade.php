<x-layouts.app>
    <div class="max-w-4xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ================= ADMIN HEADER ================= -->
        <div>
            <!-- Admin Mode Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#dea538]/10 border border-[#dea538]/30 text-[#dea538] text-xs font-bold tracking-wide mb-3">
                <svg class="w-4 h-4 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Mode Pengurus BozzBill</span>
            </div>

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                Evaluasi Handicap
            </h1>
            <p class="text-sm sm:text-base text-[#9ca3af] mt-1">
                Hingga 8 Fun Game single yang diikuti pada HC saat ini. Keputusan tetap di tangan pengurus.
            </p>
        </div>

        <!-- Flash Messages -->
        @if (session('status_success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status_success') }}</span>
            </div>
        @endif

        <!-- ================= FILTER CHIPS ================= -->
        <div class="flex flex-wrap items-center gap-2">
            <a 
                href="{{ route('evaluating-hc.index', ['filter' => 'all']) }}" 
                class="px-4 py-2 rounded-full text-xs font-bold transition {{ $activeFilter === 'all' ? 'bg-[#292c34] text-[#dea538] border border-[#dea538]' : 'bg-[#191a1e] text-[#888e9b] hover:text-white border border-[#2f323a]' }}"
            >
                Semua ({{ $counts['all'] }})
            </a>

            <a 
                href="{{ route('evaluating-hc.index', ['filter' => 'promote']) }}" 
                class="px-4 py-2 rounded-full text-xs font-bold transition {{ $activeFilter === 'promote' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500' : 'bg-[#191a1e] text-[#888e9b] hover:text-emerald-400 border border-[#2f323a]' }}"
            >
                Rekomendasi Naik ({{ $counts['promote'] }})
            </a>

            <a 
                href="{{ route('evaluating-hc.index', ['filter' => 'watch']) }}" 
                class="px-4 py-2 rounded-full text-xs font-bold transition {{ $activeFilter === 'watch' ? 'bg-amber-500/20 text-amber-400 border border-amber-500' : 'bg-[#191a1e] text-[#888e9b] hover:text-amber-400 border border-[#2f323a]' }}"
            >
                Pantau ({{ $counts['watch'] }})
            </a>

            <a 
                href="{{ route('evaluating-hc.index', ['filter' => 'stay']) }}" 
                class="px-4 py-2 rounded-full text-xs font-bold transition {{ $activeFilter === 'stay' ? 'bg-zinc-500/20 text-white border border-zinc-500' : 'bg-[#191a1e] text-[#888e9b] hover:text-white border border-[#2f323a]' }}"
            >
                Tetap ({{ $counts['stay'] }})
            </a>

            <a 
                href="{{ route('evaluating-hc.index', ['filter' => 'demote']) }}" 
                class="px-4 py-2 rounded-full text-xs font-bold transition {{ $activeFilter === 'demote' ? 'bg-rose-500/20 text-rose-400 border border-rose-500' : 'bg-[#191a1e] text-[#888e9b] hover:text-rose-400 border border-[#2f323a]' }}"
            >
                Turun ({{ $counts['demote'] }})
            </a>
        </div>

        <!-- ================= EVALUATION CARDS ================= -->
        <div class="space-y-4">
            <h2 class="text-base font-bold text-white uppercase tracking-wider">
                Tinjau Pemain
            </h2>

            @forelse ($evaluations as $item)
                @php
                    $member = $item['user'];
                    $data = $item['data'];
                    $rec = $data['recommendation'];
                @endphp

                <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-4 shadow-xl">
                    
                    <!-- Player Identity & Current HC -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-player-avatar :player="$member" class="w-11 h-11 rounded-full bg-[#16171a] border border-[#383838] text-white font-bold text-base shadow-inner" />
                            <div>
                                <h3 class="text-lg font-bold text-white tracking-tight">
                                    {{ $member->name }}
                                </h3>
                                <p class="text-xs text-[#888e9b]">
                                    {{ $member->phone }}
                                </p>
                            </div>
                        </div>

                        <div class="px-3 py-1.5 rounded-xl bg-[#151619] border border-[#333741] text-xs font-bold text-white shrink-0">
                            Current HC: <span class="text-[#dea538] font-black">{{ $member->handicap->value }}</span>
                        </div>
                    </div>

                    <!-- Performance Evidence -->
                    <p class="text-xs sm:text-sm text-[#d4d4d4] font-medium leading-relaxed">
                        {{ $data['evidence'] }}
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs text-[#959595]">
                        <span class="rounded-lg bg-[#16171a] px-2.5 py-1.5">{{ $data['total_matches'] }}/20 match</span>
                        <span class="rounded-lg bg-[#16171a] px-2.5 py-1.5">{{ $data['events_count'] }}/4 event minimum</span>
                        <span class="rounded-lg bg-[#16171a] px-2.5 py-1.5">{{ $data['opponents_count'] }}/5 lawan berbeda</span>
                    </div>
                    @if ($data['missing'])
                        <p class="text-xs text-amber-300">{{ implode(' · ', $data['missing']) }}</p>
                    @endif
                    @include('admin.evaluations.evidence', ['data' => $data])

                    <!-- Recommendation Pill -->
                    <div>
                        @if ($rec === \App\Enums\EvaluationRecommendation::Promote)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                Saran: Naik ke {{ $data['suggested_hc']->value }}
                            </span>
                        @elseif ($rec === \App\Enums\EvaluationRecommendation::Watch)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Saran: Pantau
                            </span>
                        @elseif ($rec === \App\Enums\EvaluationRecommendation::Demote)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-bold">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                Saran: Turun ke {{ $data['suggested_hc']->value }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-500/10 border border-zinc-500/30 text-zinc-300 text-xs font-bold">
                                <span class="w-2 h-2 rounded-full bg-zinc-400"></span>
                                Saran: Tetap di {{ $member->handicap->value }}
                            </span>
                        @endif
                    </div>

                    <!-- Decision Actions (Buttons) -->
                    <div class="pt-2 border-t border-[#2e313a] flex flex-col sm:flex-row items-center gap-3">
                        
                        @if ($rec === \App\Enums\EvaluationRecommendation::Promote)
                            <!-- Action 1: Setujui Naik (Gold Button) -->
                            <form method="POST" action="{{ route('evaluating-hc.approve', $member) }}" class="w-full sm:w-auto flex-1">
                                @csrf
                                <input type="hidden" name="decision" value="promote">
                                <input type="hidden" name="new_hc" value="{{ $data['suggested_hc']->value }}">
                                <input type="hidden" name="reason" value="Evaluasi HC: {{ $data['evidence'] }}">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 px-4 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold text-xs sm:text-sm transition cursor-pointer shadow-md text-center"
                                >
                                    Setujui Naik ke HC {{ $data['suggested_hc']->value }}
                                </button>
                            </form>

                            <!-- Action 2: Tetap (Outlined Button) -->
                            <form method="POST" action="{{ route('evaluating-hc.approve', $member) }}" class="w-full sm:w-auto flex-1">
                                @csrf
                                <input type="hidden" name="decision" value="stay">
                                <input type="hidden" name="new_hc" value="{{ $member->handicap->value }}">
                                <input type="hidden" name="reason" value="Pengurus memutuskan tetap di level HC saat ini">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 px-4 rounded-xl bg-[#151619] hover:bg-[#252830] border border-[#333741] text-white font-semibold text-xs sm:text-sm transition cursor-pointer text-center"
                                >
                                    Tetap HC {{ $member->handicap->value }}
                                </button>
                            </form>
                        @elseif ($rec === \App\Enums\EvaluationRecommendation::Demote)
                            <!-- Action 1: Setujui Turun (Rose Button) -->
                            <form method="POST" action="{{ route('evaluating-hc.approve', $member) }}" class="w-full sm:w-auto flex-1">
                                @csrf
                                <input type="hidden" name="decision" value="demote">
                                <input type="hidden" name="new_hc" value="{{ $data['suggested_hc']->value }}">
                                <input type="hidden" name="reason" value="Evaluasi HC: {{ $data['evidence'] }}">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 px-4 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs sm:text-sm transition cursor-pointer shadow-md text-center"
                                >
                                    Setujui Turun ke HC {{ $data['suggested_hc']->value }}
                                </button>
                            </form>

                            <!-- Action 2: Tetap -->
                            <form method="POST" action="{{ route('evaluating-hc.approve', $member) }}" class="w-full sm:w-auto flex-1">
                                @csrf
                                <input type="hidden" name="decision" value="stay">
                                <input type="hidden" name="new_hc" value="{{ $member->handicap->value }}">
                                <input type="hidden" name="reason" value="Pengurus memberi kesempatan tetap pada level saat ini">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 px-4 rounded-xl bg-[#151619] hover:bg-[#252830] border border-[#333741] text-white font-semibold text-xs sm:text-sm transition cursor-pointer text-center"
                                >
                                    Tetap HC {{ $member->handicap->value }}
                                </button>
                            </form>
                        @else
                            <!-- Action: Konfirmasi Pantau / Tetap -->
                            <form method="POST" action="{{ route('evaluating-hc.approve', $member) }}" class="w-full">
                                @csrf
                                <input type="hidden" name="decision" value="{{ $rec === \App\Enums\EvaluationRecommendation::Watch ? 'watch' : 'stay' }}">
                                <input type="hidden" name="new_hc" value="{{ $member->handicap->value }}">
                                <input type="hidden" name="reason" value="{{ $data['evidence'] }}">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 px-4 rounded-xl bg-[#151619] hover:bg-[#252830] border border-[#333741] text-white font-semibold text-xs sm:text-sm transition cursor-pointer text-center"
                                >
                                    {{ $rec === \App\Enums\EvaluationRecommendation::Watch ? 'Konfirmasi Pantau' : "Konfirmasi Tetap HC {$member->handicap->value}" }}
                                </button>
                            </form>
                        @endif

                    </div>

                </div>
            @empty
                <div class="py-12 text-center bg-[#202227] rounded-3xl border border-[#333741]">
                    <p class="text-sm font-semibold text-white">Tidak ada pemain dalam kategori filter ini.</p>
                </div>
            @endforelse

            <p class="text-center text-xs text-[#888e9b] pt-4">
                HC berubah setelah keputusan pengurus disetujui.
            </p>
        </div>

    </div>
</x-layouts.app>
