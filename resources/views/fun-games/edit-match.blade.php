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
        <form method="POST" action="{{ route('fun-games.matches.update', [$funGame, $match]) }}"
            x-data="{ player1Id: @js((string) old('player1_id', $match->player1_id)), player2Id: @js((string) old('player2_id', $match->player2_id)), winnerId: @js((string) old('winner_id', $match->winner_id)), players: @js($members->map(fn ($player) => ['id' => (string) $player->id, 'name' => $player->name])->values()->all()) }"
            x-init="$watch('player1Id', () => { if (winnerId !== player1Id && winnerId !== player2Id) winnerId = ''; }); $watch('player2Id', () => { if (winnerId !== player1Id && winnerId !== player2Id) winnerId = ''; })"
            class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([1, 2] as $side)
                    <div class="space-y-3">
                        <div>
                            <label for="edit-player{{ $side }}" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Pemain {{ $side }}</label>
                            <select id="edit-player{{ $side }}" name="player{{ $side }}_id" x-model="player{{ $side }}Id" required class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none">
                                @foreach ($members as $player)
                                    <option value="{{ $player->id }}" @selected((string) old('player'.$side.'_id', $match->{'player'.$side.'_id'}) === (string) $player->id) :disabled="'{{ $player->id }}' === player{{ $side === 1 ? 2 : 1 }}Id">{{ $player->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit-score{{ $side }}" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Skor Pemain {{ $side }}</label>
                            <input id="edit-score{{ $side }}" name="player{{ $side }}_score" type="number" inputmode="numeric" min="0" max="50" required value="{{ old('player'.$side.'_score', $match->{'player'.$side.'_score'}) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                        </div>
                    </div>
                @endforeach
            </div>
            <div>
                <label for="edit-winner" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Pemenang</label>
                <select id="edit-winner" name="winner_id" x-model="winnerId" required class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none">
                    <option value="">Pilih pemenang</option>
                    <template x-for="player in players.filter(player => player.id === player1Id || player.id === player2Id)" :key="player.id">
                        <option :value="player.id" x-text="player.name" :selected="player.id === winnerId"></option>
                    </template>
                </select>
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
    </div>
</x-layouts.app>
