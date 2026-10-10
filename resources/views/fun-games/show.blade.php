<x-layouts.app>
    <div x-data="{ showCloseModal: false }" class="max-w-5xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('fun-games.index') }}" class="text-xs text-[#dea538] hover:underline flex items-center gap-1 mb-2 font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Kembali ke Daftar Event</span>
                </a>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ $funGame->title }}</h1>
                <p class="text-xs sm:text-sm text-[#959595] mt-0.5">
                    Tanggal: {{ $funGame->event_date->format('d F Y') }} &bull; Total {{ $funGame->matches->count() }} Pertandingan
                </p>
            </div>

            <!-- Action Buttons for Admin -->
            @if (auth()->user()?->isAdmin() && $funGame->isOpen())
                <div class="flex flex-wrap items-center gap-2.5">
                    <a 
                        href="{{ route('fun-games.quick-logger') }}" 
                        class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs sm:text-sm font-bold shadow-lg shadow-[#dea538]/10 transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Input Match (Meja)</span>
                    </a>

                    <button 
                        type="button" 
                        @click="showCloseModal = true" 
                        class="px-4 py-2.5 rounded-xl bg-[#252830] hover:bg-[#2f333e] text-white border border-[#383d4a] text-xs sm:text-sm font-semibold transition"
                    >
                        Tutup & Nobatkan Juara
                    </button>
                </div>
            @endif
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
            <div role="alert" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($funGame->match_records_complete === false && $funGame->isCompleted())
            <div class="rounded-2xl border border-[#dea538]/20 bg-[#dea538]/5 p-4 text-xs text-[#d4d4d4]">
                Rekap histori belum lengkap. Statistik dihitung dari match yang tercatat; skor yang tidak tersedia ditandai Skor Tidak Tercatat.
            </div>
        @endif
        @if ($funGame->notes)
            <p class="text-xs text-[#959595] leading-relaxed">{{ $funGame->notes }}</p>
        @endif
        <!-- ================= EVENT STATUS BANNER ================= -->
        @if ($funGame->isCompleted())
            <!-- Completed Event Podium Banner -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-xl">
                <!-- Corner Gold Ribbon -->
                <div class="absolute -top-10 -right-10 w-32 h-32 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-32 h-4 bg-[#fce2b5] transform rotate-45 translate-x-10 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-32 h-6 bg-[#dea538] transform rotate-45 translate-x-8 translate-y-6 shadow-sm"></div>
                </div>

                <div class="text-center sm:text-left mb-6">
                    <span class="inline-block px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                        Event Selesai
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Podium Pemenang</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Juara 1 -->
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-[#bc852c]/20 to-[#bc852c]/5 border-2 border-[#dea538] text-center relative overflow-hidden">
                        <span class="w-8 h-8 rounded-full bg-[#dea538] text-[#141518] text-sm font-black mx-auto flex items-center justify-center mb-2 shadow">1</span>
                        <p class="text-xs uppercase tracking-widest text-[#dea538] font-bold">Juara 1 (Champion)</p>
                        <h4 class="text-lg font-black text-white mt-1">{{ $funGame->podiumName(1) }}</h4>
                        <p class="text-xs text-[#d4d4d4] mt-0.5">{{ $funGame->format === 'double' ? 'Double' : 'HC '.($funGame->champion?->handicap?->value ?? '-') }}</p>
                    </div>

                    <!-- Juara 2 -->
                    <div class="p-5 rounded-2xl bg-[#16171a] border border-[#383d4a] text-center">
                        <span class="w-8 h-8 rounded-full bg-slate-400/20 text-slate-300 text-sm font-black mx-auto flex items-center justify-center mb-2">2</span>
                        <p class="text-xs uppercase tracking-widest text-[#9ca3af] font-semibold">Juara 2 (Runner-Up)</p>
                        <h4 class="text-lg font-bold text-white mt-1">{{ $funGame->podiumName(2) }}</h4>
                        <p class="text-xs text-[#888e9b] mt-0.5">{{ $funGame->format === 'double' ? 'Double' : 'HC '.($funGame->runnerUp?->handicap?->value ?? '-') }}</p>
                    </div>

                    <!-- Juara 3 -->
                    <div class="p-5 rounded-2xl bg-[#16171a] border border-[#383d4a] text-center">
                        <span class="w-8 h-8 rounded-full bg-amber-900/20 text-amber-500 text-sm font-black mx-auto flex items-center justify-center mb-2">3</span>
                        <p class="text-xs uppercase tracking-widest text-amber-500 font-semibold">Juara 3 (3rd Place)</p>
                        <h4 class="text-lg font-bold text-white mt-1">{{ $funGame->podiumName(3) }}</h4>
                        <p class="text-xs text-[#888e9b] mt-0.5">{{ $funGame->format === 'double' ? 'Double' : 'HC '.($funGame->thirdPlace?->handicap?->value ?? '-') }}</p>
                    </div>
                </div>
            </div>
        @elseif (! $funGame->isOpen())
            <div class="rounded-2xl border border-[#333741] bg-[#202227] p-5 text-sm text-[#959595]">Event libur. Tidak ada pertandingan atau podium.</div>
        @else
            <!-- Live Active Event Banner -->
            <div class="p-5 sm:p-6 rounded-3xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                    <div>
                        <p class="text-sm font-bold text-white">Event ini sedang aktif dan menerima pencatatan match.</p>
                        <p class="text-xs text-emerald-400/80">Hasil pertandingan akan otomatis mempengaruhi evaluasi Handicap member.</p>
                    </div>
                </div>

                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('fun-games.quick-logger') }}" class="px-4 py-2 rounded-xl bg-[#dea538] text-[#141518] text-xs font-bold shrink-0">
                        Catat Match
                    </a>
                @endif
            </div>
        @endif

        <!-- ================= DAFTAR SELURUH MATCH DALAM EVENT ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-8 shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#2e313a]">
                <h3 class="text-lg font-bold text-white">Rekap Seluruh Pertandingan</h3>
                <span class="text-xs text-[#888e9b]">{{ $funGame->matches->count() }} match tercatat</span>
            </div>

            <div class="space-y-3">
                @forelse ($funGame->matches as $match)
                    <div class="p-4 rounded-2xl bg-[#16171a] border border-[#2e313a] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        
                        <!-- Duel Matchup -->
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 rounded-full bg-[#202227] border border-[#383838] flex items-center justify-center text-xs font-bold text-[#dea538] shrink-0">
                                #{{ $loop->iteration }}
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold text-sm {{ $match->winner_id == $match->player1_id ? 'text-emerald-400 font-extrabold' : 'text-white' }}">
                                        {{ $match->player1->name }}
                                        <span class="text-xs text-[#dea538] font-normal">(HC {{ $match->player1_hc->value }})</span>
                                    </span>

                                    <span class="text-xs text-[#888e9b] px-1.5 py-0.5 rounded bg-[#202227]">vs</span>

                                    <span class="font-bold text-sm {{ $match->winner_id == $match->player2_id ? 'text-emerald-400 font-extrabold' : 'text-white' }}">
                                        {{ $match->player2->name }}
                                        <span class="text-xs text-[#dea538] font-normal">(HC {{ $match->player2_hc->value }})</span>
                                    </span>
                                </div>

                                <p class="text-xs text-[#787e8c] mt-1">
                                    {{ $match->stage ?: 'Penyisihan' }} &bull; Pemenang: <strong class="text-emerald-400">{{ $match->winner->name }}</strong>
                                    @if ($match->isGiantKiller())
                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-500/10 text-[#dea538] border border-[#dea538]/30 text-[10px] font-bold">
                                            Giant Killer
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Score Display -->
                        <div class="flex flex-col items-end gap-2 sm:max-w-xs">
                            <div class="px-3.5 py-1.5 rounded-xl bg-[#202227] border border-[#333741] text-base font-black text-white">
                                <x-match-score :match="$match" />
                            </div>
                            <x-match-actions :event="$funGame" :match="$match" />
                        </div>

                    </div>
                @empty
                    <div class="py-12 text-center text-sm text-[#959595]">
                        Belum ada pertandingan yang dicatat dalam event ini.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ================= CLOSE EVENT MODAL (ADMIN) ================= -->
        @if (auth()->user()?->isAdmin() && $funGame->isOpen())
            <div 
                x-show="showCloseModal" 
                x-transition.opacity 
                class="fixed inset-0 z-50 flex items-start sm:items-center justify-center overflow-y-auto p-4 py-6 bg-black/80 backdrop-blur-sm"
                style="display: none;"
            >
                <div 
                    @click.away="showCloseModal = false" 
                    class="w-full max-w-md bg-[#202227] border border-[#333741] rounded-3xl p-6 shadow-2xl relative"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-white">Tutup Event & Nobatkan Juara</h3>
                        <button type="button" @click="showCloseModal = false" class="text-[#888e9b] hover:text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('fun-games.close', $funGame) }}" x-data="{ championId: @js((string) old('champion_id', '')), runnerId: @js((string) old('runner_up_id', '')), thirdId: @js((string) old('third_place_id', '')) }" class="space-y-4">
                        @csrf

                        <!-- Juara 1 -->
                        <div>
                            <x-player-picker :players="$members" name="champion_id" label="Juara 1" x-model="championId" :value="old('champion_id', '')" exclude="[runnerId, thirdId]" required />
                        </div>

                        <!-- Juara 2 -->
                        <div>
                            <x-player-picker :players="$members" name="runner_up_id" label="Juara 2 (opsional)" x-model="runnerId" :value="old('runner_up_id', '')" exclude="[championId, thirdId]"  />
                        </div>

                        <!-- Juara 3 -->
                        <div>
                            <x-player-picker :players="$members" name="third_place_id" label="Juara 3 (opsional)" x-model="thirdId" :value="old('third_place_id', '')" exclude="[championId, runnerId]"  />
                        </div>

                        <p class="text-[11px] text-[#888e9b] pt-1">
                            Menutup event akan mengunci penambahan match dan menganugerahkan badge Champion ke Juara 1.
                        </p>

                        <button 
                            type="submit" 
                            class="w-full bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-3 rounded-xl text-sm transition shadow-lg shadow-[#dea538]/10 cursor-pointer"
                        >
                            Konfirmasi & Tutup Event
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if (auth()->user()?->isAdmin() && ! $funGame->isOpen())
            <section x-data="{ confirmingDelete: @js($errors->has('delete_confirmation')), confirmation: '' }" class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white">Hapus Histori Fun Game</h2>
                        <p class="text-xs text-[#959595] mt-1">Hapus event ini beserta seluruh match dan podium-nya.</p>
                    </div>
                    <button type="button" x-on:click="confirmingDelete = !confirmingDelete" :aria-expanded="confirmingDelete" aria-controls="delete-history-form" class="min-h-11 px-4 rounded-xl border border-rose-500/30 text-rose-300 text-xs font-semibold hover:bg-rose-500/10 cursor-pointer">Hapus Histori</button>
                </div>
                <form id="delete-history-form" method="POST" action="{{ route('fun-games.destroy', $funGame) }}" x-show="confirmingDelete" x-cloak class="pt-3 border-t border-[#333741] space-y-3">
                    @csrf
                    @method('DELETE')
                    <p id="delete-history-impact" class="text-xs text-rose-300 leading-relaxed">Anda akan menghapus <strong>{{ $funGame->title }}</strong> dan {{ $funGame->matches->count() }} match. Statistik dan lencana terkait akan disesuaikan. Penghapusan ini permanen; akun pemain serta riwayat HC tetap tersimpan.</p>
                    <div>
                        <label for="delete-confirmation" class="block text-xs font-semibold text-[#d4d4d4] mb-1.5">Ketik HAPUS untuk konfirmasi</label>
                        <input id="delete-confirmation" name="delete_confirmation" x-model="confirmation" type="text" required autocomplete="off" spellcheck="false" aria-describedby="delete-history-impact" class="w-full sm:max-w-xs min-h-11 bg-[#16171a] border border-[#333741] focus:border-rose-400 rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" x-on:click="confirmingDelete = false; confirmation = ''" class="min-h-11 px-4 text-xs font-semibold text-[#d4d4d4] cursor-pointer">Batal</button>
                        <button type="submit" :disabled="confirmation !== 'HAPUS'" class="min-h-11 px-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-rose-500/20 cursor-pointer">Hapus Permanen</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
