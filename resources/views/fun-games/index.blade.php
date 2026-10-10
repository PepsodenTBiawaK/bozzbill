<x-layouts.app>
    <div x-data="{ showCreateModal: false }" class="max-w-6xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Event Fun Game</h1>
                <p class="text-xs sm:text-sm text-[#959595] mt-1">Arsip turnamen resmi komunitas, rekap match, dan pencatatan pertandingan meja.</p>
            </div>

            <!-- Admin Actions -->
            @if (auth()->user()?->isAdmin())
                <div class="flex items-center gap-2.5">
                    @if ($activeEvent)
                        <a 
                            href="{{ route('fun-games.quick-logger') }}" 
                            class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs sm:text-sm font-bold shadow-lg shadow-[#dea538]/10 transition flex items-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Quick Match Logger</span>
                        </a>
                    @else
                        <button 
                            type="button" 
                            @click="showCreateModal = true" 
                            class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs sm:text-sm font-bold shadow-lg shadow-[#dea538]/10 transition flex items-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Buka Event Baru</span>
                        </button>
                    @endif
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

        @if (session('status_warning'))
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ session('status_warning') }}</span>
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

        <!-- ================= ACTIVE / LIVE EVENT CARD ================= -->
        @if ($activeEvent)
            <div class="bg-gradient-to-br from-[#202227] to-[#16171a] border-2 border-[#dea538]/50 rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-2xl">
                <!-- Corner Gold Ribbon -->
                <div class="absolute -top-10 -right-10 w-32 h-32 overflow-hidden pointer-events-none">
                    <div class="absolute top-0 right-0 w-32 h-4 bg-[#fce2b5] transform rotate-45 translate-x-10 translate-y-2 opacity-90"></div>
                    <div class="absolute top-0 right-0 w-32 h-6 bg-[#dea538] transform rotate-45 translate-x-8 translate-y-6 shadow-sm"></div>
                </div>

                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                Event Sedang Berlangsung
                            </span>
                            <span class="text-xs text-[#888e9b]">&bull; {{ $activeEvent->event_date->format('d M Y') }}</span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                            {{ $activeEvent->title }}
                        </h2>

                        <p class="text-xs sm:text-sm text-[#d4d4d4] max-w-xl">
                            {{ $activeEvent->notes ?: 'Pertandingan sedang berlangsung di meja biliar Tebet. Catat hasil setiap match.' }}
                        </p>

                        <div class="pt-2 flex items-center gap-4 text-xs text-[#888e9b]">
                            <span><strong class="text-white font-bold">{{ $activeEvent->matches->count() }}</strong> Match Tercatat</span>
                            <span>&bull;</span>
                            <span>Format: Single / Fun Game Bebas</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0 w-full md:w-auto">
                        <a 
                            href="{{ route('fun-games.show', $activeEvent) }}" 
                            class="flex-1 md:flex-none text-center px-4 py-3 rounded-xl bg-[#252830] hover:bg-[#2f333e] text-white text-xs sm:text-sm font-semibold border border-[#383d4a] transition"
                        >
                            Lihat Rekap Match
                        </a>

                        @if (auth()->user()?->isAdmin())
                            <a 
                                href="{{ route('fun-games.quick-logger') }}" 
                                class="flex-1 md:flex-none text-center px-5 py-3 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs sm:text-sm font-bold shadow-lg shadow-[#dea538]/20 transition"
                            >
                                Input Match (Meja)
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- ================= ARSIP EVENT FUN GAME RESMI ================= -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Arsip Event Selesai</h2>
                    <p class="text-xs text-[#959595] mt-0.5">Histori seluruh event Fun Game resmi Komunitas Bozz Billiard</p>
                </div>
                <span class="text-xs text-[#888e9b]">
                    Total {{ $completedEvents->total() }} Event
                </span>
            </div>

            <!-- Event Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                @forelse ($completedEvents as $event)
                    <div class="bg-[#16171a] border border-[#2e313a] hover:border-[#dea538]/40 rounded-2xl p-5 transition space-y-4 flex flex-col justify-between group">
                        
                        <!-- Top Info -->
                        <div>
                            <div class="flex items-center justify-between text-xs text-[#888e9b] mb-1.5">
                                <span class="font-medium text-[#dea538]">{{ $event->event_date->format('d M Y') }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-[#202227] text-[11px] font-semibold text-[#888e9b]">
                                    {{ $event->matches_count }} Match
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white group-hover:text-[#dea538] transition truncate">
                                {{ $event->title }}
                            </h3>

                            @if ($event->notes)
                                <p class="text-xs text-[#787e8c] line-clamp-1 mt-1">
                                    {{ $event->notes }}
                                </p>
                            @endif
                        </div>

                        <!-- Podium Champions -->
                        <div class="p-3 rounded-xl bg-[#202227] border border-[#2e313a] space-y-1.5 text-xs">
                            <!-- Juara 1 -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-[#dea538]/20 border border-[#dea538]/40 text-[#dea538] text-[10px] font-black flex items-center justify-center">1</span>
                                    <span class="font-semibold text-white truncate">{{ $event->champion?->name ?? 'Belum ditentukan' }}</span>
                                </div>
                                <span class="text-[10px] uppercase tracking-wider text-[#dea538] font-bold">Juara 1</span>
                            </div>

                            <!-- Juara 2 -->
                            @if ($event->runnerUp)
                                <div class="flex items-center justify-between text-[#888e9b]">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-slate-500/20 text-slate-300 text-[10px] font-black flex items-center justify-center">2</span>
                                        <span class="truncate">{{ $event->runnerUp->name }}</span>
                                    </div>
                                    <span class="text-[10px]">Runner-Up</span>
                                </div>
                            @endif

                            <!-- Juara 3 -->
                            @if ($event->thirdPlace)
                                <div class="flex items-center justify-between text-[#888e9b]">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-amber-800/20 text-amber-500 text-[10px] font-black flex items-center justify-center">3</span>
                                        <span class="truncate">{{ $event->thirdPlace->name }}</span>
                                    </div>
                                    <span class="text-[10px]">3rd Place</span>
                                </div>
                            @endif
                        </div>

                        <!-- Bottom Link -->
                        <div class="pt-2 border-t border-[#262830] flex items-center justify-between">
                            <span class="text-xs text-[#888e9b]">Status: Selesai</span>
                            <a 
                                href="{{ route('fun-games.show', $event) }}" 
                                class="text-xs text-[#dea538] hover:underline font-bold flex items-center gap-1"
                            >
                                <span>Detail & Rekap</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-[#959595] text-sm">
                        Belum ada arsip Fun Game yang selesai.
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                {{ $completedEvents->links() }}
            </div>
        </div>

        <!-- ================= CREATE FUN GAME MODAL (ADMIN) ================= -->
        @if (auth()->user()?->isAdmin())
            <div 
                x-show="showCreateModal" 
                x-transition.opacity 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                style="display: none;"
            >
                <div 
                    @click.away="showCreateModal = false" 
                    class="w-full max-w-md bg-[#202227] border border-[#333741] rounded-3xl p-6 shadow-2xl relative"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-white">Buka Event Fun Game Baru</h3>
                        <button type="button" @click="showCreateModal = false" class="text-[#888e9b] hover:text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('fun-games.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="event_title" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Nama / Seri Event</label>
                            <input 
                                type="text" 
                                id="event_title" 
                                name="title" 
                                placeholder="Contoh: Fun Game Bozz #14 - Friday Night" 
                                required 
                                class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none"
                            />
                        </div>

                        <div>
                            <label for="event_date" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Tanggal Pelaksanaan</label>
                            <input 
                                type="date" 
                                id="event_date" 
                                name="event_date" 
                                value="{{ date('Y-m-d') }}" 
                                required 
                                class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none"
                            />
                        </div>

                        <div>
                            <label for="event_notes" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Catatan / Format (Opsional)</label>
                            <textarea 
                                id="event_notes" 
                                name="notes" 
                                rows="3" 
                                placeholder="Contoh: Race to 3, bola 9 di Meja Rasson Tebet..." 
                                class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none"
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-3 rounded-xl text-sm transition shadow-lg shadow-[#dea538]/10 cursor-pointer"
                        >
                            Buka Event Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-layouts.app>
