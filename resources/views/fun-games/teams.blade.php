@php
    $teams = $funGame->teams;
    $usedPlayers = $teams->flatMap(fn ($team) => [$team->player1_id, $team->player2_id])->map(fn ($id) => (string) $id)->values()->all();
    $canManageTeams = auth()->user()?->isAdmin() && $funGame->isOpen();
@endphp
<section id="event-teams" x-data="{ adding: @js($errors->any() && old('team_id') === 'new'), editingTeamId: @js($errors->any() ? (string) old('team_id', '') : '') }" class="rounded-3xl border border-[#333741] bg-[#202227] p-5 sm:p-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-white">Pasangan Peserta</h2>
            <p class="mt-1 text-xs text-[#959595]">{{ $teams->count() }} pasangan &bull; Tetap sepanjang event ini.</p>
        </div>
        @if ($canManageTeams)
            <button type="button" x-on:click="adding = !adding; editingTeamId = ''" :aria-expanded="adding" aria-controls="new-event-team" class="min-h-11 rounded-xl bg-[#dea538] px-4 text-xs font-bold text-[#141518] hover:bg-[#c8912c] cursor-pointer">Tambah Pasangan</button>
        @endif
    </div>
    @if ($canManageTeams)
        <form id="new-event-team" method="POST" action="{{ route('fun-games.teams.store', $funGame) }}" x-show="adding" x-cloak x-data="{ firstId: @js(old('team_id') === 'new' ? (string) old('player1_id', '') : ''), secondId: @js(old('team_id') === 'new' ? (string) old('player2_id', '') : ''), usedPlayers: @js($usedPlayers) }" class="rounded-2xl border border-[#dea538]/25 bg-[#16171a] p-4 space-y-4">
            @csrf
            <input type="hidden" name="team_id" value="new" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-player-picker :players="$members" name="player1_id" field-id="new-team-first" label="Anggota pertama" :value="old('team_id') === 'new' ? old('player1_id', '') : ''" x-model="firstId" exclude="[secondId, ...usedPlayers]" required />
                <x-player-picker :players="$members" name="player2_id" field-id="new-team-second" label="Anggota kedua" :value="old('team_id') === 'new' ? old('player2_id', '') : ''" x-model="secondId" exclude="[firstId, ...usedPlayers]" required />
            </div>
            <p class="text-xs text-[#959595]">Cari dua anggota. Anggota yang sudah berpasangan tidak ditampilkan.</p>
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="!firstId || !secondId" class="min-h-11 rounded-xl bg-[#dea538] px-4 text-xs font-bold text-[#141518] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#c8912c] cursor-pointer">Simpan Pasangan</button>
                <button type="button" x-on:click="adding = false" class="min-h-11 px-3 text-xs font-semibold text-[#d4d4d4] cursor-pointer">Batal</button>
            </div>
        </form>
    @endif
    <div class="space-y-2.5">
        @forelse ($teams as $team)
            @php
                $locked = $team->hasMatches();
                $otherPlayers = array_values(array_diff($usedPlayers, [(string) $team->player1_id, (string) $team->player2_id]));
            @endphp
            <div class="rounded-2xl border border-[#2e313a] bg-[#16171a] p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white">
                            <a href="{{ route('roster.show', $team->player1) }}" class="hover:text-[#dea538]">{{ $team->player1->name }}</a>
                            <span class="text-[#787e8c] font-normal">&amp;</span>
                            <a href="{{ route('roster.show', $team->player2) }}" class="hover:text-[#dea538]">{{ $team->player2->name }}</a>
                        </p>
                        <p class="mt-1 text-xs text-[#959595]">HC {{ $team->player1->handicap->value }} / {{ $team->player2->handicap->value }}@if ($locked) &bull; Pasangan terkunci setelah bermain.@endif</p>
                    </div>
                    @if ($canManageTeams && ! $locked)
                        <div class="flex items-center gap-2">
                            <button type="button" x-on:click="editingTeamId = editingTeamId === '{{ $team->id }}' ? '' : '{{ $team->id }}'; adding = false" :aria-expanded="editingTeamId === '{{ $team->id }}'" class="min-h-11 rounded-xl border border-[#333741] px-3 text-xs font-semibold text-[#dea538] hover:bg-[#252830] cursor-pointer">Edit</button>
                            <div x-data="{ confirming: false }">
                                <button type="button" x-on:click="confirming = !confirming" :aria-expanded="confirming" class="min-h-11 px-3 text-xs font-semibold text-rose-300 hover:bg-rose-500/10 rounded-xl cursor-pointer">Hapus</button>
                                <form method="POST" action="{{ route('fun-games.teams.destroy', [$funGame, $team]) }}" x-show="confirming" x-cloak class="flex flex-wrap items-center gap-2 pt-2 text-xs">
                                    @csrf
                                    @method('DELETE')
                                    <span class="text-[#959595]">Hapus pasangan ini?</span>
                                    <button type="submit" class="min-h-11 px-3 rounded-xl border border-rose-500/30 text-rose-300 cursor-pointer">Ya, Hapus</button>
                                    <button type="button" x-on:click="confirming = false" class="min-h-11 px-2 text-[#d4d4d4] cursor-pointer">Batal</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
                @if ($canManageTeams && ! $locked)
                    <form method="POST" action="{{ route('fun-games.teams.update', [$funGame, $team]) }}" x-show="editingTeamId === '{{ $team->id }}'" x-cloak x-data="{ firstId: @js((string) (old('team_id') === (string) $team->id ? old('player1_id', $team->player1_id) : $team->player1_id)), secondId: @js((string) (old('team_id') === (string) $team->id ? old('player2_id', $team->player2_id) : $team->player2_id)), usedPlayers: @js($otherPlayers) }" class="border-t border-[#333741] pt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="team_id" value="{{ $team->id }}" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-player-picker :players="$members" name="player1_id" :field-id="'team-'.$team->id.'-first'" label="Anggota pertama" :value="old('team_id') === (string) $team->id ? old('player1_id', $team->player1_id) : $team->player1_id" x-model="firstId" exclude="[secondId, ...usedPlayers]" required />
                            <x-player-picker :players="$members" name="player2_id" :field-id="'team-'.$team->id.'-second'" label="Anggota kedua" :value="old('team_id') === (string) $team->id ? old('player2_id', $team->player2_id) : $team->player2_id" x-model="secondId" exclude="[firstId, ...usedPlayers]" required />
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" :disabled="!firstId || !secondId" class="min-h-11 rounded-xl bg-[#dea538] px-4 text-xs font-bold text-[#141518] disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#c8912c] cursor-pointer">Simpan Pasangan</button>
                            <button type="button" x-on:click="editingTeamId = ''" class="min-h-11 px-3 text-xs font-semibold text-[#d4d4d4] cursor-pointer">Batal</button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <p class="py-4 text-center text-sm text-[#959595]">{{ $funGame->isOpen() ? 'Belum ada pasangan. Susun pasangan sebelum mencatat match double.' : 'Susunan pasangan belum tercatat dalam histori event ini.' }}</p>
        @endforelse
    </div>
</section>
