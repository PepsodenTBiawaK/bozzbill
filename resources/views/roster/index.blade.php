<x-layouts.app>
    <div x-data="{ 
        tab: '{{ $tab }}',
        showAddMemberModal: false,
        openHc: '3A',
        searchQuery: @js($search)
    }" class="max-w-4xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ================= PAGE HEADER ================= -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
                    Komunitas BozzBill
                </h1>
                <p class="text-sm sm:text-base text-[#9ca3af] mt-1">
                    Leaderboard performa & direktori roster pemain
                </p>
            </div>

            <!-- Admin Add Member Button -->
            @if (auth()->user()?->isAdmin())
                <button 
                    type="button" 
                    @click="showAddMemberModal = true"
                    class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs sm:text-sm font-bold shadow-lg shadow-[#dea538]/10 transition flex items-center justify-center gap-2 cursor-pointer shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Tambah Member</span>
                </button>
            @endif
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

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ================= SEGMENTED TABS (Figma Node 189:5289) ================= -->
        <div class="bg-[#151619] border border-[#2e313a] p-1.5 rounded-2xl flex gap-1">
            <button 
                type="button" 
                @click="tab = 'leaderboard'" 
                :class="tab === 'leaderboard' ? 'bg-[#292c34] text-[#dea538] font-bold shadow' : 'text-[#888e9b] hover:text-white'"
                class="flex-1 py-2.5 text-center rounded-xl text-xs sm:text-sm transition duration-150 cursor-pointer"
            >
                Leaderboard
            </button>
            <button 
                type="button" 
                @click="tab = 'roster'" 
                :class="tab === 'roster' ? 'bg-[#292c34] text-[#dea538] font-bold shadow' : 'text-[#888e9b] hover:text-white'"
                class="flex-1 py-2.5 text-center rounded-xl text-xs sm:text-sm transition duration-150 cursor-pointer"
            >
                Roster Anggota ({{ $totalMembers }})
            </button>
        </div>

        <!-- ================= TAB 1: LEADERBOARD ================= -->
        <div x-show="tab === 'leaderboard'" class="space-y-6">
            
            <!-- Filter Chips (Figma Node 189:5295) -->
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('roster', ['tab' => 'leaderboard', 'sort' => 'win_rate']) }}" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 {{ $sort === 'win_rate' ? 'bg-[#292c34] text-[#dea538] border border-[#dea538]' : 'bg-[#191a1e] text-[#888e9b] hover:text-white border border-[#2f323a]' }}"
                >
                    @if ($sort === 'win_rate')
                        <svg class="w-3.5 h-3.5 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                    <span>Win Rate Tertinggi</span>
                </a>

                <a 
                    href="{{ route('roster', ['tab' => 'leaderboard', 'sort' => 'most_active']) }}" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 {{ $sort === 'most_active' ? 'bg-[#292c34] text-[#dea538] border border-[#dea538]' : 'bg-[#191a1e] text-[#888e9b] hover:text-white border border-[#2f323a]' }}"
                >
                    @if ($sort === 'most_active')
                        <svg class="w-3.5 h-3.5 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                    <span>Paling Aktif Tanding</span>
                </a>

                <a 
                    href="{{ route('roster', ['tab' => 'leaderboard', 'sort' => 'most_drills']) }}" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 {{ $sort === 'most_drills' ? 'bg-[#292c34] text-[#dea538] border border-[#dea538]' : 'bg-[#191a1e] text-[#888e9b] hover:text-white border border-[#2f323a]' }}"
                >
                    @if ($sort === 'most_drills')
                        <svg class="w-3.5 h-3.5 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                    <span>Terajin Drilling</span>
                </a>
            </div>

            <!-- Podium Top 3 Players Card (Figma Node 189:5305) -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-xl">
                
                <div class="grid grid-cols-3 items-end gap-2 sm:gap-6 pt-4 pb-2">
                    
                    <!-- 2nd Place (Left) -->
                    @if ($podium['second'])
                        @php $p2 = $podium['second']; @endphp
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-2">
                                <x-player-avatar :player="$p2['user']" class="w-14 h-14 sm:w-18 sm:h-18 rounded-full bg-[#16171a] border-2 border-[#9aa0a6] text-white font-bold text-base sm:text-xl shadow-md" />
                                <div class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full bg-[#4b515d] border border-white text-white font-black text-xs flex items-center justify-center shadow">
                                    2
                                </div>
                            </div>
                            <h3 class="text-xs sm:text-sm font-bold text-white truncate max-w-[100px] sm:max-w-none">
                                {{ $p2['user']->name }}
                            </h3>
                            <p class="text-[11px] sm:text-xs text-[#9aa0a6] font-semibold mt-0.5">
                                @if ($sort === 'most_active')
                                    {{ $p2['total_matches'] }} Match
                                @elseif ($sort === 'most_drills')
                                    {{ $p2['drills_count'] }}x Drill
                                @else
                                    Win Rate {{ $p2['win_rate'] }}%
                                @endif
                            </p>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <!-- 1st Place (Center - Elevated with Crown) -->
                    @if ($podium['first'])
                        @php $p1 = $podium['first']; @endphp
                        <div class="flex flex-col items-center text-center -translate-y-2 sm:-translate-y-3">
                            <!-- Gold Crown Icon -->
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-[#dea538] mb-1 drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5m14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                            </svg>

                            <div class="relative mb-2">
                                <x-player-avatar :player="$p1['user']" class="w-18 h-18 sm:w-24 sm:h-24 rounded-full bg-[#16171a] border-3 sm:border-4 border-[#dea538] text-[#dea538] font-black text-xl sm:text-3xl shadow-xl shadow-[#dea538]/10" />
                                <div class="absolute -bottom-2 inset-x-0 flex justify-center">
                                    <span class="w-7 h-7 rounded-full bg-[#dea538] border-2 border-[#141518] text-[#141518] font-black text-xs flex items-center justify-center shadow">
                                        1
                                    </span>
                                </div>
                            </div>
                            
                            <h3 class="text-sm sm:text-base font-bold text-white mt-1">
                                {{ $p1['user']->name }}
                            </h3>
                            <p class="text-xs sm:text-sm text-[#dea538] font-bold mt-0.5">
                                @if ($sort === 'most_active')
                                    {{ $p1['total_matches'] }} Match
                                @elseif ($sort === 'most_drills')
                                    {{ $p1['drills_count'] }}x Drill
                                @else
                                    Win Rate {{ $p1['win_rate'] }}%
                                @endif
                            </p>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <!-- 3rd Place (Right) -->
                    @if ($podium['third'])
                        @php $p3 = $podium['third']; @endphp
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-2">
                                <x-player-avatar :player="$p3['user']" class="w-14 h-14 sm:w-18 sm:h-18 rounded-full bg-[#16171a] border-2 border-[#c88d4b] text-white font-bold text-base sm:text-xl shadow-md" />
                                <div class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full bg-[#7a4e1d] border border-white text-white font-black text-xs flex items-center justify-center shadow">
                                    3
                                </div>
                            </div>
                            <h3 class="text-xs sm:text-sm font-bold text-white truncate max-w-[100px] sm:max-w-none">
                                {{ $p3['user']->name }}
                            </h3>
                            <p class="text-[11px] sm:text-xs text-[#c88d4b] font-semibold mt-0.5">
                                @if ($sort === 'most_active')
                                    {{ $p3['total_matches'] }} Match
                                @elseif ($sort === 'most_drills')
                                    {{ $p3['drills_count'] }}x Drill
                                @else
                                    Win Rate {{ $p3['win_rate'] }}%
                                @endif
                            </p>
                        </div>
                    @else
                        <div></div>
                    @endif

                </div>

            </div>

            <!-- Rankings List (Rank 4+) (Figma Node 189:5331) -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl space-y-3">
                <div class="flex items-center justify-between text-xs font-semibold text-[#888e9b] uppercase tracking-wider px-2 pb-1 border-b border-[#2e313a]">
                    <span>Peringkat Pemain</span>
                    <span>Performa</span>
                </div>

                <div class="divide-y divide-[#252830]">
                    @forelse ($restRankings as $idx => $item)
                        @php
                            $rankNum = $idx + 4;
                            $m = $item['user'];
                        @endphp

                        <div class="py-3 px-2 flex items-center justify-between gap-3 hover:bg-[#252830]/50 rounded-xl transition">
                            
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 text-center text-sm font-bold text-[#888e9b] shrink-0">
                                    {{ $rankNum }}
                                </span>

                                <x-player-avatar :player="$m" class="w-9 h-9 rounded-full bg-[#16171a] border border-[#333741] text-white text-xs font-bold" />

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-white truncate">
                                            {{ $m->name }}
                                        </p>
                                        <span class="text-[10px] font-bold text-[#dea538] px-1.5 py-0.5 rounded bg-[#dea538]/10 border border-[#dea538]/20">
                                            HC {{ $m->handicap->value }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#888e9b] mt-0.5">
                                        {{ $item['total_matches'] }} tanding &bull; {{ $item['wins'] }} menang
                                    </p>
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                <div class="px-2.5 py-1 rounded-xl bg-[#151619] border border-[#333741] text-xs font-bold text-white inline-block">
                                    @if ($sort === 'most_active')
                                        {{ $item['total_matches'] }} Match
                                    @elseif ($sort === 'most_drills')
                                        {{ $item['drills_count'] }}x Drill
                                    @else
                                        {{ $item['win_rate'] }}%
                                    @endif
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-[#888e9b]">
                            Semua anggota telah masuk dalam jajaran Podium Top 3.
                        </div>
                    @endforelse
                </div>

            </div>

        </div>

        <!-- ================= TAB 2: ROSTER ANGGOTA (Figma Node 189:5432) ================= -->
        <div x-show="tab === 'roster'" class="space-y-4" style="display: none;">
            
            <!-- Search Bar -->
            <form method="GET" action="{{ route('roster') }}" class="flex items-end gap-2">
                <input type="hidden" name="tab" value="roster">
                <x-player-picker :players="$searchPlayers" name="search" label="Cari anggota" :filter="true" :query="$search" class="flex-1 min-w-0" x-on:player-selected="$el.closest('form').requestSubmit()" />
                <button type="submit" class="min-h-11 px-4 rounded-xl bg-[#dea538] text-[#141518] text-sm font-bold cursor-pointer">Cari</button>
            </form>

            <p class="text-xs text-[#888e9b] px-1">
                Pilih grup handicap, lalu buka profil anggota untuk melihat performanya.
            </p>

            <!-- Accordion Groups per Handicap Tier -->
            <div class="space-y-3">
                @foreach ($rosterByHandicap as $tier => $tierData)
                    <div class="bg-[#202227] border border-[#333741] rounded-2xl overflow-hidden shadow-md">
                        
                        <!-- Accordion Header -->
                        <button 
                            type="button" 
                            @click="openHc = openHc === '{{ $tier }}' ? '' : '{{ $tier }}'"
                            class="w-full px-5 py-4 flex items-center justify-between transition cursor-pointer text-left"
                            :class="openHc === '{{ $tier }}' ? 'bg-[#252830]' : 'hover:bg-[#252830]/60'"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm sm:text-base font-bold text-white" :class="openHc === '{{ $tier }}' ? 'text-[#dea538]' : 'text-white'">
                                    Handicap {{ $tier }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-[#888e9b] font-medium">
                                    {{ $tierData['count'] }} Pemain
                                </span>
                                <svg 
                                    class="w-4 h-4 text-[#888e9b] transition-transform duration-200"
                                    :class="openHc === '{{ $tier }}' ? 'rotate-180 text-[#dea538]' : ''" 
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Accordion Content -->
                        <div x-show="openHc === '{{ $tier }}'" class="divide-y divide-[#2a2d36] px-4 pb-2 border-t border-[#2e313a]">
                            @forelse ($tierData['members'] as $mItem)
                                @php $m = $mItem['user']; @endphp
                                <div class="py-3 flex items-center justify-between gap-3">
                                    <a href="{{ route('roster.show', $m) }}" class="flex items-center gap-3 min-w-0 group">
                                        <x-player-avatar :player="$m" class="w-10 h-10 rounded-full bg-[#16171a] border border-[#383838] text-white text-xs font-bold" />

                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-white group-hover:text-[#dea538] transition truncate">
                                                {{ $m->name }}
                                            </p>
                                            <p class="text-[11px] text-[#888e9b]">
                                                {{ $mItem['total_matches'] }} Match &bull; {{ $mItem['wins'] }} Menang
                                            </p>
                                        </div>
                                    </a>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="px-2.5 py-1 rounded-xl bg-[#151619] border border-[#333741] text-xs font-bold text-[#dea538]">
                                            WR {{ $mItem['win_rate'] }}%
                                        </span>

                                        <a
                                            href="{{ route('roster.show', $m) }}"
                                            title="Profil Anggota {{ $m->name }}"
                                            aria-label="Profil Anggota {{ $m->name }}"
                                            class="w-8 h-8 rounded-xl bg-[#151619] hover:bg-[#dea538] hover:text-[#141518] text-[#888e9b] border border-[#333741] flex items-center justify-center transition cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21v-2a8 8 0 0116 0v2" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-[#888e9b]">
                                    Belum ada anggota di level HC {{ $tier }}.
                                </div>
                            @endforelse
                        </div>

                    </div>
                @endforeach
            </div>

        </div>

        <!-- ================= MODAL: TAMBAH MEMBER (ADMIN) ================= -->
        @if (auth()->user()?->isAdmin())
            <div 
                x-show="showAddMemberModal" 
                x-transition.opacity 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                style="display: none;"
            >
                <div 
                    @click.away="showAddMemberModal = false" 
                    class="w-full max-w-md bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 shadow-2xl relative text-left"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-[#dea538]/10 border border-[#dea538]/30 flex items-center justify-center text-[#dea538]">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Tambah Member Komunitas</h3>
                        </div>

                        <button 
                            type="button" 
                            @click="showAddMemberModal = false"
                            class="text-[#888e9b] hover:text-white cursor-pointer"
                        >
                            &times;
                        </button>
                    </div>

                    <p class="text-xs text-[#9ca3af] mb-5">
                        Setelah didaftarkan, pemain langsung bisa dicatat dalam pertandingan. Pemain perlu mengaktifkan akun dan membuat password di halaman Aktivasi Akun untuk login.
                    </p>

                    <form method="POST" action="{{ route('members.store') }}" class="space-y-4">
                        @csrf

                        <!-- Nama Pemain -->
                        <div>
                            <label class="block text-xs font-semibold text-[#d4d4d4] uppercase tracking-wider mb-1.5">
                                Nama Lengkap
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Contoh: Rian Billiard" 
                                class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white placeholder-[#555a66] text-sm outline-none transition"
                            />
                        </div>

                        <!-- No WhatsApp -->
                        <div>
                            <label class="block text-xs font-semibold text-[#d4d4d4] uppercase tracking-wider mb-1.5">
                                No WhatsApp
                            </label>
                            <input 
                                type="tel" 
                                name="phone" 
                                required 
                                placeholder="Contoh: 081234567890" 
                                class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white placeholder-[#555a66] text-sm outline-none transition"
                            />
                        </div>

                        <!-- Handicap Awal -->
                        <div>
                            <label class="block text-xs font-semibold text-[#d4d4d4] uppercase tracking-wider mb-1.5">
                                Handicap Awal
                            </label>
                            <select 
                                name="handicap" 
                                required 
                                class="w-full bg-[#151619] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-3 text-white text-sm outline-none transition cursor-pointer"
                            >
                                <option value="3SB">3SB - Pemula</option>
                                <option value="3B">3B - Berkembang</option>
                                <option value="3N" selected>3N - Menengah (Standar)</option>
                                <option value="3A">3A - Pemain Kuat</option>
                                <option value="3+">3+ - Tingkat Tertinggi</option>
                            </select>
                        </div>

                        <div class="pt-2">
                            <button 
                                type="submit" 
                                class="w-full bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-3.5 rounded-xl text-sm transition duration-150 shadow-lg shadow-[#dea538]/10 cursor-pointer"
                            >
                                Simpan & Daftarkan Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-layouts.app>
