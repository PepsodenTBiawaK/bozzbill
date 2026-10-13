<x-layouts.app>
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <a href="{{ route('fun-games.show', $funGame) }}" class="inline-flex items-center min-h-11 text-xs font-semibold text-[#dea538] hover:underline">&larr; Kembali ke Event</a>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">Edit Match</h1>
            <p class="text-sm text-[#959595] mt-1">{{ $funGame->title }} &bull; Koreksi tersedia selama event berjalan.</p>
        </div>
        @if ($errors->any())
            <div role="alert" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        @if ($funGame->isDouble())
            @php
                $participants = $funGame->teams->map(fn ($team) => ['id' => $team->id, 'name' => $team->displayName(), 'handicap' => $team->player1->handicap->value.' / '.$team->player2->handicap->value])->values()->all();
                $recorderValues = [
                    'p1Id' => (string) old('team1_id', $match->team1_id),
                    'p2Id' => (string) old('team2_id', $match->team2_id),
                    'p1Score' => (int) old('player1_score', $match->player1_score),
                    'p2Score' => (int) old('player2_score', $match->player2_score),
                    'winnerId' => (string) old('winner_team_id', $match->winner_team_id),
                    'stage' => old('stage', $match->stage),
                ];
            @endphp
            <form method="POST" action="{{ route('fun-games.matches.update', [$funGame, $match]) }}" x-data="matchRecorder(@js($participants), @js($recorderValues))" class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-5">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ([1, 2] as $side)
                        <div class="space-y-3">
                            <x-team-picker :teams="$funGame->teams" :name="'team'.$side.'_id'" :label="'Tim '.$side" :value="old('team'.$side.'_id', $match->{'team'.$side.'_id'})" x-model="p{{ $side }}Id" exclude="[p{{ $side === 1 ? 2 : 1 }}Id]" required />
                            <div>
                                <label for="edit-team-score{{ $side }}" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Skor Tim {{ $side }}</label>
                                <input id="edit-team-score{{ $side }}" name="player{{ $side }}_score" x-model.number="p{{ $side }}Score" type="number" inputmode="numeric" min="0" max="50" required value="{{ old('player'.$side.'_score', $match->{'player'.$side.'_score'}) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <input type="hidden" name="winner_team_id" :value="winnerId" />
                <p class="text-xs text-[#959595]">Pemenang mengikuti skor. Skor kedua tim harus berbeda.</p>
                <div>
                    <label for="edit-double-stage" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Babak / Ronde</label>
                    <input id="edit-double-stage" name="stage" x-model="stage" type="text" maxlength="50" value="{{ old('stage', $match->stage) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="!p1Id || !p2Id || !winnerId" class="min-h-11 px-5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">Simpan Perubahan</button>
                    <a href="{{ route('fun-games.show', $funGame) }}" class="min-h-11 inline-flex items-center px-4 text-sm text-[#d4d4d4]">Batal</a>
                </div>
            </form>
        @else
        <form method="POST" action="{{ route('fun-games.matches.update', [$funGame, $match]) }}"
            x-data="{ player1Id: @js((string) old('player1_id', $match->player1_id)), player2Id: @js((string) old('player2_id', $match->player2_id)), winnerId: @js((string) old('winner_id', $match->winner_id)) }"
            class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([1, 2] as $side)
                    <div class="space-y-3">
                        <div>
                            <x-player-picker :players="$members" :name="'player'.$side.'_id'" :label="'Pemain '.$side" :value="old('player'.$side.'_id', $match->{'player'.$side.'_id'})" x-model="player{{ $side }}Id" exclude="[player{{ $side === 1 ? 2 : 1 }}Id]" required />
                        </div>
                        <div>
                            <label for="edit-score{{ $side }}" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Skor Pemain {{ $side }}</label>
                            <input id="edit-score{{ $side }}" name="player{{ $side }}_score" type="number" inputmode="numeric" min="0" max="50" required value="{{ old('player'.$side.'_score', $match->{'player'.$side.'_score'}) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                        </div>
                    </div>
                @endforeach
            </div>
            <div>
                <x-player-picker :players="$members" name="winner_id" label="Pemenang" :value="old('winner_id', $match->winner_id)" x-model="winnerId" allowed="[player1Id, player2Id]" required />
            </div>
            <div>
                <label for="edit-stage" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Babak / Ronde</label>
                <input id="edit-stage" name="stage" type="text" maxlength="50" value="{{ old('stage', $match->stage) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
            </div>
            <p class="text-xs text-[#959595]">HC pemain yang tetap dalam match mengikuti catatan awal. Pemain pengganti menggunakan HC saat koreksi disimpan.</p>
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="min-h-11 px-5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold cursor-pointer">Simpan Perubahan</button>
                <a href="{{ route('fun-games.show', $funGame) }}" class="min-h-11 inline-flex items-center px-4 text-sm text-[#d4d4d4]">Batal</a>
            </div>
        </form>
        @endif
    </div>
</x-layouts.app>
