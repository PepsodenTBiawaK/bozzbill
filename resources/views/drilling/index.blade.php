<x-layouts.app>
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ================= GREETING / PAGE HEADER ================= -->
        <div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                Jurnal Drilling
            </h1>
            <p class="text-sm sm:text-base text-[#9ca3af] mt-1">
                Catat hasil drilling yang kamu lakukan!
            </p>
        </div>

        <!-- ================= PLAYER SUMMARY ================= -->
        <div class="grid grid-cols-3 lg:grid-cols-12 gap-2 sm:gap-3">
            
            <!-- Player Identity -->
            <div class="col-span-3 lg:col-span-5 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex items-center justify-between gap-3">
                <!-- Watermark Logo Mark in Background -->
                <div class="absolute -right-4 -bottom-4 w-24 h-24 opacity-5 pointer-events-none select-none">
                    <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="w-full h-full object-contain filter invert" />
                </div>

                <div class="flex items-center gap-3 min-w-0 relative z-10">
                    <x-player-avatar :player="$user" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-[#16171a] border-2 border-[#383838] text-white font-bold text-sm sm:text-base shadow-inner" />

                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs uppercase tracking-wider font-semibold text-[#dea538] truncate">
                            BOZZBILL PLAYER
                        </p>
                        <h2 class="text-sm sm:text-base font-bold text-white tracking-tight mt-0.5 truncate" title="{{ $user->name }}">
                            {{ $user->name }}
                        </h2>
                    </div>
                </div>

                <div class="relative z-10 text-right shrink-0">
                    <div class="inline-flex flex-col items-center">
                        <span class="text-sm sm:text-base font-black text-[#dea538] tracking-wider whitespace-nowrap">
                            HC {{ $user->handicap->value }}
                        </span>
                        <div class="w-full h-0.5 bg-[#dea538] rounded-full mt-0.5"></div>
                    </div>
                </div>
            </div>

            <!-- Akurasi -->
            <div class="lg:col-span-2 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $overallAccuracy }}%
                    </p>
                    <p class="text-[10px] sm:text-xs leading-tight font-semibold text-[#dea538] mt-1">
                        Akurasi
                    </p>
                </div>
            </div>

            <!-- Total Drilling -->
            <div class="lg:col-span-2 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $totalDrills }}
                    </p>
                    <p class="text-[10px] sm:text-xs leading-tight font-semibold text-[#dea538] mt-1">
                        Total Drilling
                    </p>
                </div>
            </div>

            <!-- Drilling Bulan Ini -->
            <div class="lg:col-span-3 min-w-0 bg-[#202227] border border-[#333741] rounded-2xl p-3 sm:p-4 relative overflow-hidden shadow-xl flex flex-col justify-between">
                <div class="absolute -top-8 -right-8 w-20 h-20 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-20 h-3 bg-[#fce2b5] transform rotate-45 translate-x-6 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-20 h-4 bg-[#dea538] transform rotate-45 translate-x-4 translate-y-4 shadow-sm"></div>
                </div>

                <div class="relative z-10">
                    <p class="text-xl sm:text-2xl font-black text-white tracking-tight leading-none tabular-nums break-all">
                        {{ $thisMonthCount }}
                    </p>
                    <p class="text-[10px] sm:text-xs leading-tight font-semibold text-[#dea538] mt-1">
                        Drilling Bulan Ini
                    </p>
                </div>
            </div>

        </div>

        <!-- Flash Message -->
        @if (session('status_success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

        <!-- ================= MIDDLE CARD: CATAT SESI LATIHAN (Figma Node 188:3180) ================= -->
        <div 
            x-data="{
                drillName: '{{ old('drill_name', '') }}',
                success: {{ old('success_count', 0) }},
                fail: {{ old('fail_count', 0) }},
                notes: '{{ old('notes', '') }}',
                
                get totalAttempts() {
                    return this.success + this.fail;
                },
                
                get accuracy() {
                    if (this.totalAttempts === 0) return 0;
                    return Math.round((this.success / this.totalAttempts) * 100);
                },

                setPreset(name) {
                    this.drillName = name;
                    this.success = 0;
                    this.fail = 0;
                }
            }"
            class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl"
        >
            <div class="border-b border-[#2e313a] pb-4">
                <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                    Catat Sesi Latihan
                </h3>
                <p class="text-xs text-[#959595] mt-0.5">
                    Input hasil latihan mandiri di meja biliar untuk melacak peningkatan akurasi
                </p>
            </div>

            <form method="POST" action="{{ route('drilling.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="practice_date" value="{{ now()->toDateString() }}">

                <!-- Nama Drilling -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="drill_name_input" class="block text-xs font-semibold text-[#d4d4d4] uppercase tracking-wider">
                            Nama Drilling
                        </label>
                        <span class="text-[11px] text-[#888e9b]">Pilih preset atau ketik bebas</span>
                    </div>

                    <input 
                        type="text" 
                        id="drill_name_input" 
                        name="drill_name" 
                        x-model="drillName"
                        required 
                        placeholder="Contoh: Stop Shot 9-Ball, Potting Sudut" 
                        class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-2xl px-4 py-3.5 text-white placeholder-[#555a66] text-sm sm:text-base outline-none transition"
                    />

                    <!-- Quick Preset Badges -->
                    <div class="flex flex-wrap gap-2 mt-2.5">
                        <button 
                            type="button" 
                            @click="setPreset('Stop Shot 10x Corner Pocket')"
                            class="px-2.5 py-1 rounded-lg bg-[#151619] hover:bg-[#282a32] border border-[#333741] text-[11px] text-[#d4d4d4] transition cursor-pointer"
                        >
                            + Stop Shot 10x
                        </button>
                        <button 
                            type="button" 
                            @click="setPreset('Cut Shot 45° Angle')"
                            class="px-2.5 py-1 rounded-lg bg-[#151619] hover:bg-[#282a32] border border-[#333741] text-[11px] text-[#d4d4d4] transition cursor-pointer"
                        >
                            + Cut Shot 45°
                        </button>
                        <button 
                            type="button" 
                            @click="setPreset('Draw Shot Stun Ball Control')"
                            class="px-2.5 py-1 rounded-lg bg-[#151619] hover:bg-[#282a32] border border-[#333741] text-[11px] text-[#d4d4d4] transition cursor-pointer"
                        >
                            + Draw Shot Control
                        </button>
                        <button 
                            type="button" 
                            @click="setPreset('Straight Long Potting Drill')"
                            class="px-2.5 py-1 rounded-lg bg-[#151619] hover:bg-[#282a32] border border-[#333741] text-[11px] text-[#d4d4d4] transition cursor-pointer"
                        >
                            + Long Potting
                        </button>
                    </div>
                </div>

                <!-- Attempt Counters (Berhasil & Gagal with Stepper and Accuracy Badge in Center) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 pt-2">
                    
                    <!-- Stepper 1: Berhasil (Green) -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-[#151619] border border-[#2e313a]">
                        <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-3">
                            Berhasil (Potting Masuk)
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <button 
                                type="button" 
                                @click="if (success > 0) success--"
                                class="w-12 h-12 rounded-xl bg-[#202227] hover:bg-[#2a2d34] border border-[#333741] active:scale-95 text-white text-xl font-bold flex items-center justify-center transition cursor-pointer"
                            >
                                &minus;
                            </button>

                            <div class="text-center">
                                <span class="text-3xl sm:text-4xl font-black text-white" x-text="success"></span>
                                <input type="hidden" name="success_count" :value="success">
                                <p class="text-[10px] text-[#888e9b] uppercase tracking-wider mt-0.5">Kali Sukses</p>
                            </div>

                            <button 
                                type="button" 
                                @click="success++"
                                class="w-12 h-12 rounded-xl bg-[#202227] hover:bg-[#2a2d34] border border-[#333741] active:scale-95 text-emerald-400 text-xl font-bold flex items-center justify-center transition cursor-pointer"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Stepper 2: Gagal (Rose) -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-[#151619] border border-[#2e313a]">
                        <p class="text-xs font-bold text-rose-400 uppercase tracking-wider mb-3">
                            Gagal (Meleset / Fault)
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <button 
                                type="button" 
                                @click="if (fail > 0) fail--"
                                class="w-12 h-12 rounded-xl bg-[#202227] hover:bg-[#2a2d34] border border-[#333741] active:scale-95 text-white text-xl font-bold flex items-center justify-center transition cursor-pointer"
                            >
                                &minus;
                            </button>

                            <div class="text-center">
                                <span class="text-3xl sm:text-4xl font-black text-white" x-text="fail"></span>
                                <input type="hidden" name="fail_count" :value="fail">
                                <p class="text-[10px] text-[#888e9b] uppercase tracking-wider mt-0.5">Kali Gagal</p>
                            </div>

                            <button 
                                type="button" 
                                @click="fail++"
                                class="w-12 h-12 rounded-xl bg-[#202227] hover:bg-[#2a2d34] border border-[#333741] active:scale-95 text-rose-400 text-xl font-bold flex items-center justify-center transition cursor-pointer"
                            >
                                +
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Real-time Accuracy Pill Banner (Figma Node 188:3807) -->
                <div class="flex items-center justify-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-bold shadow-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span><span x-text="accuracy"></span>% Akurasi Sesi Ini</span>
                        <span class="text-xs font-normal text-emerald-400/70" x-text="'(' + totalAttempts + ' total percobaan)'"></span>
                    </div>
                </div>

                <!-- Catatan Teknis -->
                <div>
                    <label for="notes_input" class="block text-xs font-semibold text-[#d4d4d4] uppercase tracking-wider mb-2">
                        Catatan Teknik / Evaluasi Diri
                    </label>
                    <textarea 
                        id="notes_input" 
                        name="notes" 
                        x-model="notes"
                        rows="2" 
                        placeholder="Contoh: Fokus konsistensi follow-through dan stabilitas jembatan tangan." 
                        class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-2xl px-4 py-3 text-white placeholder-[#555a66] text-sm outline-none transition resize-none"
                    ></textarea>
                </div>

                <!-- Submit Button (Prominent Gold Button) -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-[#dea538] to-[#fce2b5] hover:from-[#c8912c] hover:to-[#ebcd9a] text-[#141518] font-bold py-4 rounded-2xl text-base transition duration-150 shadow-lg shadow-[#dea538]/10 cursor-pointer active:scale-[0.99] flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5 text-[#141518]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <span>Simpan Latihan</span>
                </button>
            </form>
        </div>

        <!-- ================= BOTTOM CARD: RIWAYAT DRILLING (Figma Node 188:3505) ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                        Riwayat Drilling
                    </h3>
                    <p class="text-xs text-[#959595] mt-0.5">
                        Jurnal seluruh sesi latihan mandiri yang pernah Anda rekam
                    </p>
                </div>

                <span class="text-xs text-[#dea538] font-semibold px-3 py-1 rounded-full bg-[#dea538]/10 border border-[#dea538]/20">
                    {{ $recentDrills->total() }} Sesi
                </span>
            </div>

            <!-- Drills List -->
            <div class="space-y-4">
                @forelse ($recentDrills as $drill)
                    <div class="p-4 sm:p-5 rounded-2xl bg-[#191a1e] border border-[#2f323a] space-y-3 shadow-md">
                        
                        <!-- Top Row: Drill Name & Date -->
                        <div class="flex items-start justify-between gap-3">
                            <h4 class="text-base font-bold text-white tracking-tight">
                                {{ $drill->drill_name }}
                            </h4>
                            <span class="text-xs text-[#888e9b] shrink-0 font-medium">
                                {{ $drill->practice_date->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <!-- Second Row: Badges & Accuracy % -->
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                                    Berhasil: {{ $drill->success_count }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold">
                                    Gagal: {{ $drill->fail_count }}
                                </span>
                            </div>

                            <span class="text-sm sm:text-base font-black text-white">
                                {{ $drill->accuracy_percentage }}%
                            </span>
                        </div>

                        <!-- Third Row: Visual Accuracy Progress Bar Track -->
                        <div class="w-full h-2 bg-[#141518] rounded-full overflow-hidden border border-[#2e313a]">
                            <div 
                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" 
                                style="width: {{ $drill->accuracy_percentage }}%"
                            ></div>
                        </div>

                        <!-- Notes (if any) -->
                        @if ($drill->notes)
                            <p class="text-xs text-[#888e9b] italic pt-1 border-t border-[#252830]">
                                &ldquo;{{ $drill->notes }}&rdquo;
                            </p>
                        @endif

                    </div>
                @empty
                    <div class="py-12 text-center bg-[#191a1e] rounded-2xl border border-[#2f323a]">
                        <svg class="w-12 h-12 text-[#555a66] mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" stroke-width="1.5" /><circle cx="12" cy="12" r="5" stroke-width="1.5" /><circle cx="12" cy="12" r="1" stroke-width="1.5" />
                        </svg>
                        <p class="text-sm font-semibold text-white">Belum Ada Sesi Drilling</p>
                        <p class="text-xs text-[#888e9b] mt-1">
                            Gunakan formulir di atas untuk mulai mencatat sesi latihan mandiri Anda.
                        </p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($recentDrills->hasPages())
                <div class="pt-4 border-t border-[#2e313a]">
                    {{ $recentDrills->links() }}
                </div>
            @endif

        </div>

    </div>
</x-layouts.app>
