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
                <x-player-avatar :player="$user" class="w-20 h-20 rounded-full bg-[#16171a] border-2 border-[#dea538]/60 text-2xl font-black text-[#dea538] shadow-lg" />

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
                            <span class="text-[11px] text-[#888e9b]">Win Rate Single:</span>
                            <span class="text-xs font-bold text-emerald-400">{{ $user->winRatePercentage() }}%</span>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl bg-[#16171a] border border-[#333741] flex items-center gap-2">
                            <span class="text-[11px] text-[#888e9b]">Match Single:</span>
                            <span class="text-xs font-bold text-white">{{ $user->totalMatchesCount() }}</span>
                        </div>
                    </div>
                    <a href="{{ route('drilling') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#dea538] hover:underline mt-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Jurnal Drilling
                    </a>
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
                
                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                    x-data="avatarEditor(@js((bool) $user->avatar), @js((bool) old('remove_avatar', false)))"
                    @submit="guardSubmit($event)"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'" value="{{ (bool) old('remove_avatar', false) ? '1' : '0' }}" />
                    <div>
                        <label for="avatar-picker" class="block text-xs font-medium text-[#d4d4d4] mb-2">Foto Profil</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-[#16171a] border-2 border-[#dea538]/60 text-[#dea538] text-xl font-bold flex items-center justify-center shrink-0 overflow-hidden">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" alt="Pratinjau foto profil" class="w-full h-full object-cover" />
                                </template>
                                <div x-show="!previewUrl && !removeAvatar" class="w-full h-full" style="{{ (bool) old('remove_avatar', false) ? 'display: none;' : '' }}">
                                    <x-player-avatar :player="$user" class="w-full h-full" />
                                </div>
                                <span x-show="!previewUrl && removeAvatar" style="{{ (bool) old('remove_avatar', false) ? '' : 'display: none;' }}">{{ $user->initials }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <label for="avatar-picker" class="inline-flex items-center px-3 py-2 rounded-xl bg-[#252830] hover:bg-[#2f333e] border border-[#383d4a] text-white text-xs font-semibold cursor-pointer transition">Pilih Foto</label>
                                <input type="file" id="avatar-picker" accept="image/jpeg,image/png,image/webp" @change="chooseAvatar($event)" :disabled="exporting" class="sr-only" />
                                <input type="file" name="avatar" x-ref="avatarInput" class="hidden" aria-hidden="true" tabindex="-1" />
                                <button type="button" @click="clearAvatar()" x-show="(hasAvatar || previewUrl) && !removeAvatar" style="{{ ! $user->avatar || (bool) old('remove_avatar', false) ? 'display: none;' : '' }}" class="text-xs text-rose-400 hover:text-rose-300 font-medium ml-3 cursor-pointer">Hapus Foto</button>
                                <p class="text-[11px] text-[#787e8c] mt-2">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                                <button type="button" @click="editCrop()" x-show="sourceApplied && !editing" style="display: none;" class="text-xs font-semibold text-[#dea538] hover:underline mt-2 cursor-pointer">Atur Posisi</button>
                            </div>
                        </div>
                        <p x-show="loading" style="display: none;" role="status" class="text-xs text-[#959595] mt-3">Menyiapkan foto…</p>
                        <div x-show="editing" style="display: none;" class="mt-4 p-4 rounded-2xl bg-[#16171a] border border-[#333741] space-y-4">
                            <div>
                                <h4 class="text-sm font-bold text-white">Atur Posisi Foto</h4>
                                <p id="avatar-crop-help" class="text-xs text-[#888e9b] mt-1">Geser foto dan atur zoom. Bagian di dalam lingkaran akan tampil sebagai foto profil.</p>
                            </div>
                            <div
                                tabindex="0" role="group" aria-label="Posisi foto profil. Gunakan tombol panah untuk menggeser."
                                aria-describedby="avatar-crop-help"
                                @pointerdown.prevent="startDrag($event)" @pointermove="drag($event)"
                                @pointerup="endDrag($event)" @pointercancel="endDrag($event)" @lostpointercapture="endDrag($event)"
                                @keydown.arrow-left.prevent="movePhoto(-0.025, 0)" @keydown.arrow-right.prevent="movePhoto(0.025, 0)"
                                @keydown.arrow-up.prevent="movePhoto(0, -0.025)" @keydown.arrow-down.prevent="movePhoto(0, 0.025)"
                                class="relative w-full max-w-56 aspect-square mx-auto rounded-full overflow-hidden ring-2 ring-[#dea538]/60 bg-[#202227] touch-none select-none cursor-grab active:cursor-grabbing focus:outline-none focus:ring-[#dea538]"
                            >
                                <template x-if="sourceUrl && editing">
                                    <img :src="sourceUrl" :style="imageStyle()" draggable="false" alt="Pratinjau posisi foto" class="absolute max-w-none pointer-events-none select-none" />
                                </template>
                            </div>
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <label for="avatar-zoom" class="text-xs font-semibold text-[#d4d4d4]">Zoom</label>
                                    <button type="button" @click="resetCrop()" :disabled="exporting" class="text-xs text-[#dea538] hover:underline cursor-pointer">Posisi Awal</button>
                                </div>
                                <input id="avatar-zoom" type="range" min="1" max="3" step="0.01" :value="zoom" @input="setZoom($event.target.value)" :disabled="exporting" class="w-full h-8 accent-[#dea538] cursor-pointer" />
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cancelCrop()" class="flex-1 min-h-11 rounded-xl border border-[#383d4a] text-xs font-semibold text-[#d4d4d4] hover:bg-[#252830] cursor-pointer">Batal</button>
                                <button type="button" @click="applyCrop()" :disabled="exporting" x-text="exporting ? 'Memproses…' : 'Gunakan Foto'" class="flex-1 min-h-11 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-xs font-bold text-[#141518] disabled:opacity-50 cursor-pointer">Gunakan Foto</button>
                            </div>
                        </div>
                        <p x-show="previewError" x-text="previewError" role="alert" class="text-xs text-rose-400 mt-2"></p>
                        <p x-show="previewUrl" style="display: none;" class="text-xs text-[#dea538] mt-2">Foto baru akan disimpan saat Anda memilih Simpan Profil.</p>
                        <p x-show="removeAvatar" style="{{ (bool) old('remove_avatar', false) ? '' : 'display: none;' }}" class="text-xs text-[#959595] mt-2">Foto akan dihapus saat Anda memilih Simpan Profil.</p>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Nama Lengkap</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $user->name) }}" 
                            maxlength="100"
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
                        :disabled="editing || loading || exporting"
                        class="w-full mt-2 bg-[#dea538] hover:bg-[#c8912c] text-[#141518] font-bold py-2.5 rounded-xl text-sm transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
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
