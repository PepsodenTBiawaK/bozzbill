<x-layouts.app>
    <div class="max-w-4xl mx-auto space-y-6 sm:space-y-8">
        <div>
            <a href="{{ route('roster', ['tab' => 'roster']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#dea538] hover:underline mb-3">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Roster
            </a>
            <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Profil Anggota</h1>
        </div>

        @if (session('status_success'))
            <div role="status" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
                {{ session('status_success') }}
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

        <section class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <x-player-avatar :player="$member" class="w-16 h-16 rounded-full bg-[#16171a] border-2 border-[#dea538]/60 text-xl font-bold text-[#dea538]" />

                <div class="min-w-0 flex-1">
                    <h2 class="text-xl sm:text-2xl font-bold text-white break-words">{{ $member->name }}</h2>
                    <p class="text-sm text-[#959595] mt-1">{{ $member->maskedPhone() }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="px-2.5 py-1 rounded-lg bg-[#dea538]/10 border border-[#dea538]/30 text-xs font-bold text-[#dea538]">
                            HC {{ $member->handicap->value }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $member->is_active ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-zinc-500/10 border border-zinc-500/20 text-[#959595]' }}">
                            {{ $member->is_active ? 'Akun Aktif' : 'Belum Aktivasi' }}
                        </span>
                    </div>
                    @if ($member->created_at)
                        <p class="text-xs text-[#888e9b] mt-3">Bergabung {{ $member->created_at->format('d M Y') }}</p>
                    @endif
                </div>

                @if ($member->id !== auth()->id())
                    <a href="{{ route('head-to-head', ['opponent_id' => $member->id]) }}" class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-xs font-bold transition shrink-0">
                        Lihat Head to Head
                    </a>
                @endif
            </div>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-[#2e313a]">
                <div class="rounded-2xl bg-[#16171a] border border-[#2e313a] p-4">
                    <dt class="text-xs text-[#959595]">Win Rate</dt>
                    <dd class="text-2xl font-bold text-[#dea538] mt-1">{{ $stats['win_rate'] }}%</dd>
                </div>
                <div class="rounded-2xl bg-[#16171a] border border-[#2e313a] p-4">
                    <dt class="text-xs text-[#959595]">Total Match</dt>
                    <dd class="text-2xl font-bold text-white mt-1">{{ $stats['total_matches'] }}</dd>
                </div>
                <div class="rounded-2xl bg-[#16171a] border border-[#2e313a] p-4">
                    <dt class="text-xs text-[#959595]">Menang</dt>
                    <dd class="text-2xl font-bold text-white mt-1">{{ $stats['wins'] }}</dd>
                </div>
                <div class="rounded-2xl bg-[#16171a] border border-[#2e313a] p-4">
                    <dt class="text-xs text-[#959595]">Fun Game</dt>
                    <dd class="text-2xl font-bold text-white mt-1">{{ $stats['fun_games_count'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-bold text-white">Lencana Prestasi</h2>
                <span class="text-xs font-semibold text-[#dea538]">{{ $badges->count() }} Diperoleh</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($badges as $badge)
                    <div class="flex items-start gap-3 p-4 rounded-2xl bg-[#16171a] border border-[#2e313a]">
                        <div class="w-10 h-10 rounded-xl bg-[#dea538]/10 border border-[#dea538]/30 text-[#dea538] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                @if ($badge->icon === 'target')
                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                    <circle cx="12" cy="12" r="6" stroke-width="2" />
                                    <circle cx="12" cy="12" r="2" stroke-width="2" />
                                @elseif ($badge->icon === 'zap')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                @elseif ($badge->icon === 'swords')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                @elseif ($badge->icon === 'trophy')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                @endif
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-white">{{ $badge->name }}</h3>
                            <p class="text-xs text-[#959595] mt-1 leading-relaxed">{{ $badge->description }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[#959595] sm:col-span-2">Belum ada lencana yang diperoleh.</p>
                @endforelse
            </div>
        </section>

        @if ($recentMatches->isNotEmpty())
            <section class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4">Pertandingan Terbaru</h2>
                <div class="space-y-3">
                    @foreach ($recentMatches as $match)
                        @php
                            $isWinner = $match->winner_id === $member->id;
                            $isPlayerOne = $match->player1_id === $member->id;
                            $opponent = $isPlayerOne ? $match->player2 : $match->player1;
                            $memberScore = $isPlayerOne ? $match->player1_score : $match->player2_score;
                            $opponentScore = $isPlayerOne ? $match->player2_score : $match->player1_score;
                        @endphp
                        <div class="flex items-center justify-between gap-3 p-3.5 rounded-2xl bg-[#16171a] border border-[#2e313a]">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-white truncate">vs {{ $opponent->name }}</p>
                                <p class="text-xs text-[#888e9b] mt-0.5 truncate">{{ $match->funGame->title }} &bull; {{ $match->stage ?? 'Fun Game Match' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-base font-bold {{ $isWinner ? 'text-emerald-400' : 'text-rose-400' }}">{{ $memberScore }} - {{ $opponentScore }}</p>
                                <p class="text-xs text-[#888e9b]">{{ $isWinner ? 'Menang' : 'Kalah' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (auth()->user()?->isAdmin())
            <section x-data="{ resetOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-white">Pengaturan Akun Anggota</h2>
                        @if (! $member->is_active)
                            <p class="text-sm text-[#959595] mt-1">Akun belum diaktifkan. Member perlu melakukan aktivasi akun terlebih dahulu.</p>
                        @else
                            <p class="text-xs text-[#959595] mt-1">Masukkan password baru untuk mengganti password akun anggota ini.</p>
                        @endif
                    </div>
                    @if ($member->is_active)
                        <button type="button" @click="resetOpen = !resetOpen" :aria-expanded="resetOpen" aria-controls="reset-password-form" class="px-4 py-2.5 rounded-xl bg-[#252830] hover:bg-[#2f333e] border border-[#383d4a] text-white text-xs font-bold transition cursor-pointer shrink-0">
                            Reset Password
                        </button>
                    @endif
                </div>

                @if ($member->is_active)
                    <form id="reset-password-form" x-show="resetOpen" x-transition.opacity style="display: none;" method="POST" action="{{ route('roster.reset-password', $member) }}" class="mt-5 pt-5 border-t border-[#2e313a] space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Password Baru (minimal 6 karakter)</label>
                                <input type="password" id="password" name="password" minlength="6" required autocomplete="new-password" class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition" />
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-xs font-medium text-[#d4d4d4] mb-1.5">Konfirmasi Password Baru</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" minlength="6" required autocomplete="new-password" class="w-full bg-[#16171a] border border-[#333741] focus:border-[#dea538] focus:ring-1 focus:ring-[#dea538] rounded-xl px-4 py-2.5 text-white text-sm outline-none transition" />
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold transition cursor-pointer">
                            Simpan Password Baru
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</x-layouts.app>
