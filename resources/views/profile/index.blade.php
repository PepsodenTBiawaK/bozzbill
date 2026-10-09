<x-layouts.app>
    <div class="max-w-4xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Profil Anggota</h1>
                <p class="text-xs sm:text-sm text-[#959595] mt-1">Identitas resmi pemain, data akun, dan riwayat transparansi Handicap.</p>
            </div>

            <!-- Role Badge -->
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->isAdmin() ? 'bg-amber-500/10 text-[#dea538] border border-[#dea538]/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/30' }}">
                {{ $user->role->label() }}
            </span>
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

        <!-- Card 1: Player Identity Card (with Gold Ribbon) -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-xl">
            <!-- Corner Gold Ribbon -->
            <div class="absolute -top-10 -right-10 w-32 h-32 overflow-hidden pointer-events-none">
                <div class="absolute top-0 right-0 w-32 h-4 bg-[#fce2b5] transform rotate-45 translate-x-10 translate-y-2 opacity-90"></div>
                <div class="absolute top-0 right-0 w-32 h-6 bg-[#dea538] transform rotate-45 translate-x-8 translate-y-6 shadow-sm"></div>
            </div>

            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 relative z-10 text-center sm:text-left">
                <!-- Avatar -->
                <div class="w-20 h-20 rounded-full bg-[#16171a] border-2 border-[#dea538]/60 flex items-center justify-center text-2xl font-black text-[#dea538] shrink-0 shadow-lg">
                    {{ $user->initials }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="text-xs uppercase tracking-widest font-semibold text-[#dea538]">Paspor Bozz Billiard</p>
                    <h2 class="text-2xl font-bold text-white mt-1">{{ $user->name }}</h2>
                    <p class="text-xs sm:text-sm text-[#959595] mt-1 flex items-center justify-center sm:justify-start gap-1.5">
                        <span>{{ $user->phone }}</span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] text-emerald-400 font-semibold">Aktif</span>
                    </p>

                    <!-- Stats mini badges -->
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mt-4">
                        <div class="px-3 py-1.5 rounded-xl bg-[#16171a] border border-[#333741] flex items-center gap-2">
                            <span class="text-[11px] text-[#888e9b]">Handicap:</span>
                            <span class="text-xs font-bold text-[#dea538]">{{ $user->handicap->value }}</span>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl bg-[#16171a] border border-[#333741] flex items-center gap-2">
                            <span class="text-[11px] text-[#888e9b]">Win Rate:</span>
                            <span class="text-xs font-bold text-emerald-400">{{ $user->winRatePercentage() }}%</span>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl bg-[#16171a] border border-[#333741] flex items-center gap-2">
                            <span class="text-[11px] text-[#888e9b]">Total Match:</span>
                            <span class="text-xs font-bold text-white">{{ $user->totalMatchesCount() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Timeline Riwayat Handicap (HC) -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 sm:p-8 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight">Timeline Riwayat Handicap (HC)</h3>
                    <p class="text-xs text-[#959595] mt-0.5">Transparansi keputusan pengurus berdasarkan evaluasi 4 Fun Game</p>
                </div>
                <div class="px-3 py-1 rounded-full bg-[#dea538]/10 border border-[#dea538]/30 text-xs font-bold text-[#dea538]">
                    Saat Ini: HC {{ $user->handicap->value }}
                </div>
            </div>

            <!-- Timeline List -->
            <div class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#333741]">
                @forelse ($hcHistories as $history)
                    <div class="relative">
                        <!-- Timeline Dot -->
                        <div class="absolute -left-6 sm:-left-8 top-1.5 w-3.5 h-3.5 rounded-full bg-[#dea538] border-4 border-[#202227] shadow"></div>

                        <div class="p-4 rounded-2xl bg-[#16171a] border border-[#2e313a] space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md bg-[#252830] text-xs font-semibold text-[#888e9b]">
                                        {{ $history->old_hc ? 'HC ' . $history->old_hc->value : 'Awal' }}
                                    </span>
                                    <svg class="w-3.5 h-3.5 text-[#dea538]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                    <span class="px-2.5 py-0.5 rounded-md bg-[#dea538]/15 border border-[#dea538]/30 text-xs font-bold text-[#dea538]">
                                        HC {{ $history->new_hc->value }}
                                    </span>
                                </div>

                                <span class="text-xs text-[#888e9b]">
                                    {{ $history->approved_at->format('d M Y') }}
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm text-[#d4d4d4] leading-relaxed">
                                {{ $history->reason }}
                            </p>

                            @if ($history->changer)
                                <p class="text-[11px] text-[#787e8c]">
                                    Disetujui oleh: <span class="text-white font-medium">{{ $history->changer->name }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="relative">
                        <div class="absolute -left-6 sm:-left-8 top-1.5 w-3.5 h-3.5 rounded-full bg-emerald-400 border-4 border-[#202227]"></div>
                        <div class="p-4 rounded-2xl bg-[#16171a] border border-[#2e313a]">
                            <p class="text-xs sm:text-sm text-white font-semibold">Handicap Standar Anggota Baru: HC {{ $user->handicap->value }}</p>
                            <p class="text-xs text-[#959595] mt-1">Evaluasi otomatis akan dikalkulasikan setelah Anda bermain di 4 event Fun Game resmi.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Card 3: Form Pengaturan Akun -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Update Profil -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-bold text-white mb-4">Informasi Profil</h3>
                
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Nama Lengkap</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $user->name) }}" 
                            required 
                            class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#888e9b] mb-1.5">No WhatsApp (Terkunci)</label>
                        <input 
                            type="text" 
                            value="{{ $user->phone }}" 
                            disabled 
                            class="w-full bg-[#16171a]/50 border border-[#2a2d36] rounded-xl px-4 py-2.5 text-[#787e8c] text-sm cursor-not-allowed"
                        />
                        <p class="text-[11px] text-[#787e8c] mt-1">Hubungi pengurus jika ingin mengubah nomor WhatsApp akun Anda.</p>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full mt-2 bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-2.5 rounded-xl text-sm transition cursor-pointer"
                    >
                        Simpan Profil
                    </button>
                </form>
            </div>

            <!-- Update Password -->
            <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 shadow-xl">
                <h3 class="text-lg font-bold text-white mb-4">Ubah Password</h3>
                
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Password Saat Ini</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required 
                            class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition"
                        />
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Password Baru (min 6)</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition"
                        />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Konfirmasi Password Baru</label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            required 
                            class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition"
                        />
                    </div>

                    <button 
                        type="submit" 
                        class="w-full mt-2 bg-[#252830] hover:bg-[#2f333e] text-white border border-[#383d4a] font-bold py-2.5 rounded-xl text-sm transition cursor-pointer"
                    >
                        Perbarui Password
                    </button>
                </form>
            </div>

        </div>

        <!-- Keluar Akun Card -->
        <div class="bg-[#202227] border border-[#333741] rounded-3xl p-6 shadow-xl flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-white">Sesi Akun</h4>
                <p class="text-xs text-[#959595] mt-0.5">Keluar dari sesi aplikasi di perangkat ini.</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button 
                    type="submit" 
                    onclick="return confirm('Apakah Anda yakin ingin keluar dari akun?')"
                    class="px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold transition cursor-pointer"
                >
                    Keluar Akun
                </button>
            </form>
        </div>

    </div>
</x-layouts.app>
