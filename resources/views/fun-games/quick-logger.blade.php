<x-layouts.app>
    <div 
        x-data="{
            p1Id: @js((string) old('player1_id', '')),
            p2Id: @js((string) old('player2_id', '')),
            p1Score: @js((int) old('player1_score', 0)),
            p2Score: @js((int) old('player2_score', 0)),
            winnerId: @js((string) old('winner_id', '')),
            stage: 'Penyisihan',
            members: {{ Js::from($members) }},

            getP1() {
                return this.members.find(m => m.id == this.p1Id);
            },
            getP2() {
                return this.members.find(m => m.id == this.p2Id);
            },
            updateWinner() {
                if (this.p1Score > this.p2Score) {
                    this.winnerId = this.p1Id;
                } else if (this.p2Score > this.p1Score) {
                    this.winnerId = this.p2Id;
                } else {
                    this.winnerId = '';
                }
            }
        }"
        x-init="$watch('p1Score', () => updateWinner()); $watch('p2Score', () => updateWinner()); $watch('p1Id', () => updateWinner()); $watch('p2Id', () => updateWinner())"
        class="max-w-4xl mx-auto space-y-6 sm:space-y-8"
    >
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-bold uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Meja
                    </span>
                    <span class="text-xs text-[#888e9b]">&bull; {{ $event->title }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Quick Match Logger</h1>
                <p class="text-xs sm:text-sm text-[#959595] mt-0.5">Input hasil pertandingan biliar langsung di pinggir meja hitungan detik.</p>
            </div>

            <a 
                href="{{ route('fun-games.show', $event) }}" 
                class="px-3.5 py-2 rounded-xl bg-[#202227] hover:bg-[#2b2e35] text-xs font-semibold text-[#dea538] border border-[#333741] transition"
            >
                Kembali ke Event
            </a>
        </div>

        <!-- Flash Messages -->
        @if (session('status_success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status_success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ================= RAPID MATCH INPUT CARD ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-8 relative shadow-2xl">
            <!-- Corner Gold Ribbon -->
            <div class="absolute -top-10 -right-10 w-32 h-32 overflow-hidden pointer-events-none">
                <div class="absolute top-0 right-0 w-32 h-4 bg-[#fce2b5] transform rotate-45 translate-x-10 translate-y-2 opacity-90"></div>
                <div class="absolute top-0 right-0 w-32 h-6 bg-[#dea538] transform rotate-45 translate-x-8 translate-y-6 shadow-sm"></div>
            </div>

            <form method="POST" action="{{ route('fun-games.matches.store', $event) }}" class="space-y-6">
                @csrf

                <!-- Stage Selection -->
                <div class="flex items-center justify-between pb-4 border-b border-[#2e313a]">
                    <span class="text-xs font-semibold text-[#888e9b] uppercase tracking-wider">Babak / Ronde:</span>
                    <select 
                        name="stage" 
                        x-model="stage" 
                        class="bg-[#16171a] border border-[#333741] rounded-xl px-3 py-1.5 text-xs text-white outline-none focus:border-[#dea538]"
                    >
                        <option value="Penyisihan">Babak Penyisihan</option>
                        <option value="16 Besar">16 Besar</option>
                        <option value="Perempat Final">Perempat Final (8 Besar)</option>
                        <option value="Semifinal">Semifinal</option>
                        <option value="Final">Final</option>
                    </select>
                </div>

                <!-- Matchup Duel Grid (Player 1 vs Player 2) -->
                <div class="grid grid-cols-1 md:grid-cols-11 gap-4 items-center">
                    
                    <!-- Player 1 Box (md:col-span-5) -->
                    <div class="md:col-span-5 bg-[#16171a] border rounded-2xl p-4 sm:p-5 transition" :class="winnerId == p1Id && p1Id ? 'border-emerald-500/60 ring-1 ring-emerald-500/40' : 'border-[#2e313a]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#888e9b]">Pemain 1</span>
                            <template x-if="getP1()">
                                <span class="px-2 py-0.5 rounded bg-[#dea538]/10 text-[#dea538] text-xs font-black" x-text="'HC ' + getP1().handicap"></span>
                            </template>
                        </div>

                        <!-- Player 1 Selector -->
                        <div class="mb-4">
                            <x-player-picker :players="$members" name="player1_id" label="Cari Pemain 1" x-model="p1Id" :value="old('player1_id', '')" exclude="[p2Id]" required />
                        </div>

                        <!-- Player 1 Score Stepper -->
                        <div>
                            <span class="block text-center text-xs text-[#888e9b] mb-2 font-medium">Skor Rack / Game</span>
                            <div class="flex items-center justify-center gap-3">
                                <button 
                                    type="button" 
                                    @click="p1Score = Math.max(0, p1Score - 1)" 
                                    class="w-11 h-11 rounded-xl bg-[#202227] hover:bg-[#2b2e35] text-white border border-[#333741] font-bold text-lg flex items-center justify-center active:scale-95 transition cursor-pointer"
                                >
                                    &minus;
                                </button>

                                <input 
                                    type="number" 
                                    name="player1_score" 
                                    x-model="p1Score" 
                                    min="0" 
                                    max="50" 
                                    class="w-16 text-center text-3xl sm:text-4xl font-black text-white bg-transparent outline-none"
                                />

                                <button 
                                    type="button" 
                                    @click="p1Score = p1Score + 1" 
                                    class="w-11 h-11 rounded-xl bg-[#202227] hover:bg-[#2b2e35] text-white border border-[#333741] font-bold text-lg flex items-center justify-center active:scale-95 transition cursor-pointer"
                                >
                                    &#43;
                                </button>
                            </div>
                        </div>

                        <!-- Winner indicator button -->
                        <div class="mt-4 pt-3 border-t border-[#252830] text-center">
                            <button 
                                type="button" 
                                @click="winnerId = p1Id" 
                                :disabled="!p1Id" 
                                class="w-full py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                                :class="winnerId == p1Id && p1Id ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-[#202227] text-[#888e9b] hover:text-white'"
                            >
                                <span x-text="winnerId == p1Id && p1Id ? '&#10003; Pemenang Match' : 'Pilih Jadi Pemenang'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- VS Badge Center (md:col-span-1) -->
                    <div class="md:col-span-1 flex flex-col items-center justify-center py-2 md:py-0">
                        <div class="w-10 h-10 rounded-full bg-[#16171a] border border-[#333741] flex items-center justify-center text-xs font-black text-[#dea538] shadow">
                            VS
                        </div>
                    </div>

                    <!-- Player 2 Box (md:col-span-5) -->
                    <div class="md:col-span-5 bg-[#16171a] border rounded-2xl p-4 sm:p-5 transition" :class="winnerId == p2Id && p2Id ? 'border-emerald-500/60 ring-1 ring-emerald-500/40' : 'border-[#2e313a]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#888e9b]">Pemain 2</span>
                            <template x-if="getP2()">
                                <span class="px-2 py-0.5 rounded bg-[#dea538]/10 text-[#dea538] text-xs font-black" x-text="'HC ' + getP2().handicap"></span>
                            </template>
                        </div>

                        <!-- Player 2 Selector -->
                        <div class="mb-4">
                            <x-player-picker :players="$members" name="player2_id" label="Cari Pemain 2" x-model="p2Id" :value="old('player2_id', '')" exclude="[p1Id]" required />
                        </div>

                        <!-- Player 2 Score Stepper -->
                        <div>
                            <span class="block text-center text-xs text-[#888e9b] mb-2 font-medium">Skor Rack / Game</span>
                            <div class="flex items-center justify-center gap-3">
                                <button 
                                    type="button" 
                                    @click="p2Score = Math.max(0, p2Score - 1)" 
                                    class="w-11 h-11 rounded-xl bg-[#202227] hover:bg-[#2b2e35] text-white border border-[#333741] font-bold text-lg flex items-center justify-center active:scale-95 transition cursor-pointer"
                                >
                                    &minus;
                                </button>

                                <input 
                                    type="number" 
                                    name="player2_score" 
                                    x-model="p2Score" 
                                    min="0" 
                                    max="50" 
                                    class="w-16 text-center text-3xl sm:text-4xl font-black text-white bg-transparent outline-none"
                                />

                                <button 
                                    type="button" 
                                    @click="p2Score = p2Score + 1" 
                                    class="w-11 h-11 rounded-xl bg-[#202227] hover:bg-[#2b2e35] text-white border border-[#333741] font-bold text-lg flex items-center justify-center active:scale-95 transition cursor-pointer"
                                >
                                    &#43;
                                </button>
                            </div>
                        </div>

                        <!-- Winner indicator button -->
                        <div class="mt-4 pt-3 border-t border-[#252830] text-center">
                            <button 
                                type="button" 
                                @click="winnerId = p2Id" 
                                :disabled="!p2Id" 
                                class="w-full py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                                :class="winnerId == p2Id && p2Id ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-[#202227] text-[#888e9b] hover:text-white'"
                            >
                                <span x-text="winnerId == p2Id && p2Id ? '&#10003; Pemenang Match' : 'Pilih Jadi Pemenang'"></span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Hidden Input Winner ID -->
                <input type="hidden" name="winner_id" :value="winnerId" />

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    :disabled="!p1Id || !p2Id || !winnerId"
                    class="w-full bg-[#dea538] hover:bg-[#c8912c] disabled:opacity-40 disabled:cursor-not-allowed text-[#141518] font-black py-4 rounded-2xl text-base sm:text-lg transition shadow-xl shadow-[#dea538]/20 cursor-pointer active:scale-[0.99]"
                >
                    Simpan Hasil Pertandingan
                </button>
            </form>
        </div>

        <!-- ================= RECENT MATCHES IN THIS EVENT ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-bold text-white">Pertandingan Terakhir di Event Ini</h3>
                <span class="text-xs text-[#888e9b]">{{ $recentMatches->count() }} match</span>
            </div>

            <div class="space-y-2.5">
                @forelse ($recentMatches as $m)
                    <div class="p-3.5 rounded-2xl bg-[#16171a] border border-[#2e313a] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-7 h-7 rounded-full bg-[#202227] border border-[#383838] flex items-center justify-center text-[10px] font-bold text-[#dea538] shrink-0">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-white truncate">
                                    <span class="{{ $m->winner_id == $m->player1_id ? 'text-emerald-400 font-extrabold' : 'text-[#d4d4d4]' }}">{{ $m->player1->name }}</span>
                                    <span class="text-xs text-[#787e8c] font-normal">({{ $m->player1_score }})</span>
                                    <span class="text-xs text-[#888e9b] mx-1">vs</span>
                                    <span class="{{ $m->winner_id == $m->player2_id ? 'text-emerald-400 font-extrabold' : 'text-[#d4d4d4]' }}">{{ $m->player2->name }}</span>
                                    <span class="text-xs text-[#787e8c] font-normal">({{ $m->player2_score }})</span>
                                </p>
                                <p class="text-[11px] text-[#787e8c] mt-0.5">
                                    {{ $m->stage ?: 'Penyisihan' }} &bull; Pemenang: <strong class="text-emerald-400">{{ $m->winner->name }}</strong>
                                </p>
                            </div>
                        </div>

                        <div class="text-right space-y-2 sm:max-w-xs">
                            <span class="text-sm font-black text-white bg-[#202227] px-2.5 py-1 rounded-lg border border-[#333741]">
                                <x-match-score :match="$m" />
                            </span>
                            <x-match-actions :event="$event" :match="$m" />
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-[#959595]">
                        Belum ada match yang dicatat pada event ini.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-layouts.app>
