<x-layouts.app>
    @php
        $isHistoricalCorrection = $funGame->isCompleted();
        $usesScoreWinner = $funGame->isDouble() || $isHistoricalCorrection;
        $participantField = $funGame->isDouble() ? 'team' : 'player';
        $winnerField = $funGame->isDouble() ? 'winner_team_id' : 'winner_id';
        $participantLabel = $funGame->isDouble() ? 'Tim' : 'Pemain';
        $participants = $funGame->isDouble()
            ? $funGame->teams->map(fn ($team) => ['id' => $team->id, 'name' => $team->displayName()])->values()->all()
            : $members->map(function ($member) use ($match) {
                $hc = $member->id === $match->player1_id ? $match->player1_hc : ($member->id === $match->player2_id ? $match->player2_hc : $member->handicap);
                return ['id' => $member->id, 'name' => $member->name, 'handicapRank' => $hc?->rank()];
            })->values()->all();
        $recorderOptions = ['format' => $funGame->format, 'voorLabels' => \App\Enums\MatchVoor::labels(), 'autoWinner' => $usesScoreWinner];
        if (! $funGame->isDouble()) {
            $recorderOptions['originalIds'] = [(string) $match->player1_id, (string) $match->player2_id];
            $recorderOptions['originalVoor'] = $match->voorSummary();
        }
        $recorderValues = [
            'p1Id' => (string) old($participantField.'1_id', $match->{$participantField.'1_id'}),
            'p2Id' => (string) old($participantField.'2_id', $match->{$participantField.'2_id'}),
            'p1Score' => (int) old('player1_score', $match->player1_score),
            'p2Score' => (int) old('player2_score', $match->player2_score),
            'winnerId' => (string) old($winnerField, $match->{$winnerField}),
            'stage' => old('stage', $match->stage ?? 'Penyisihan'),
        ];
        $stageOptions = [
            'Penyisihan' => 'Babak Penyisihan',
            '16 Besar' => '16 Besar',
            'Perempat Final' => 'Perempat Final (8 Besar)',
            'Semifinal' => 'Semifinal',
            'Perebutan Juara 3' => 'Perebutan Juara 3',
            'Final' => 'Final',
        ];
    @endphp
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <a href="{{ route('fun-games.show', $funGame) }}" class="inline-flex items-center min-h-11 text-xs font-semibold text-[#dea538] hover:underline">&larr; Kembali ke Event</a>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $isHistoricalCorrection ? 'Koreksi Match Selesai' : 'Edit Match' }}</h1>
            <p class="text-sm text-[#959595] mt-1">{{ $funGame->title }} &bull; {{ $isHistoricalCorrection ? 'Koreksi histori khusus Superadmin.' : 'Koreksi tersedia selama event berjalan.' }}</p>
        </div>
        @if ($errors->any())
            <div role="alert" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
                @error('match_version')
                    <a href="{{ route('fun-games.matches.edit', [$funGame, $match]) }}" class="min-h-11 inline-flex items-center font-semibold underline">Muat ulang data match</a>
                @enderror
            </div>
        @endif
        @if ($isHistoricalCorrection)
            <section aria-labelledby="original-match-heading" class="rounded-2xl border border-[#333741] bg-[#202227] p-4 sm:p-5 space-y-2">
                <h2 id="original-match-heading" class="text-xs font-semibold uppercase tracking-wide text-[#959595]">Hasil yang tercatat</h2>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-semibold text-white">
                    <span>{{ $match->sideName(1) }}</span>
                    <span class="text-[#dea538]">{{ $match->scoreFor() }}</span>
                    <span>{{ $match->sideName(2) }}</span>
                </div>
                <p class="text-xs text-[#959595]">{{ $match->stage ?: 'Penyisihan' }} &bull; Pemenang: {{ $match->winnerName() }}</p>
            </section>
        @endif
        <form method="POST" action="{{ route('fun-games.matches.update', [$funGame, $match]) }}"
            x-data="matchRecorder(@js($participants), @js($recorderValues), @js($recorderOptions))"
            class="bg-[#202227] border border-[#333741] rounded-3xl p-5 sm:p-6 space-y-5">
            @csrf
            @method('PUT')
            <div class="rounded-xl border border-[#333741] bg-[#16171a] px-3 py-2.5 space-y-1" aria-live="polite">
                @unless ($funGame->isDouble())
                    <p class="text-xs font-semibold text-[#dea538]" x-text="voorPreview"></p>
                    <p class="text-[11px] text-[#888e9b]">Skor akhir termasuk frame pemberian. HC peserta lama mengikuti catatan awal.</p>
                @endunless
                <p class="text-xs text-[#959595]" x-text="racePreview"></p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([1, 2] as $side)
                    <div class="space-y-3">
                        @if ($funGame->isDouble())
                            <x-team-picker :teams="$funGame->teams" :name="'team'.$side.'_id'" :label="'Tim '.$side" :value="old('team'.$side.'_id', $match->{'team'.$side.'_id'})" x-model="p{{ $side }}Id" exclude="[p{{ $side === 1 ? 2 : 1 }}Id]" required />
                        @else
                            <x-player-picker :players="$members" :name="'player'.$side.'_id'" :label="'Pemain '.$side" :value="old('player'.$side.'_id', $match->{'player'.$side.'_id'})" x-model="p{{ $side }}Id" exclude="[p{{ $side === 1 ? 2 : 1 }}Id]" required />
                        @endif
                        <div>
                            <label for="edit-score{{ $side }}" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Skor {{ $participantLabel }} {{ $side }}</label>
                            <input id="edit-score{{ $side }}" name="player{{ $side }}_score" x-model.number="p{{ $side }}Score" type="number" inputmode="numeric" min="0" max="50" required value="{{ old('player'.$side.'_score', $match->{'player'.$side.'_score'}) }}" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none" />
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($usesScoreWinner)
                <input type="hidden" name="{{ $winnerField }}" :value="winnerId" />
                <p class="text-xs text-[#959595]">Pemenang mengikuti skor. Skor kedua {{ $funGame->isDouble() ? 'tim' : 'pemain' }} harus berbeda.</p>
            @else
                <div>
                    <x-player-picker :players="$members" name="winner_id" label="Pemenang" :value="old('winner_id', $match->winner_id)" x-model="winnerId" allowed="[p1Id, p2Id]" required />
                </div>
            @endif
            <div>
                <label for="edit-stage" class="block text-xs text-[#d4d4d4] font-semibold mb-1.5">Babak / Ronde</label>
                <select id="edit-stage" name="stage" x-model="stage" class="w-full min-h-11 bg-[#16171a] border border-[#333741] focus:border-[#dea538] rounded-xl px-3 py-2.5 text-white text-sm outline-none">
                    @if ($recorderValues['stage'] && ! isset($stageOptions[$recorderValues['stage']]))
                        <option value="{{ $recorderValues['stage'] }}" selected>{{ $recorderValues['stage'] }} (tercatat)</option>
                    @endif
                    @foreach ($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected($recorderValues['stage'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @unless ($funGame->isDouble())
                <p class="text-xs text-[#959595]">HC pemain yang tetap dalam match mengikuti catatan awal. Pemain pengganti menggunakan HC saat koreksi disimpan.</p>
            @endunless
            @if ($isHistoricalCorrection)
                @include('fun-games.match-correction-confirmation', ['match' => $match])
            @endif
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" :disabled="!p1Id || !p2Id || !winnerId" class="min-h-11 px-5 rounded-xl bg-[#dea538] hover:bg-[#c8912c] text-[#141518] text-sm font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">{{ $isHistoricalCorrection ? 'Simpan Koreksi Histori' : 'Simpan Perubahan' }}</button>
                <a href="{{ route('fun-games.show', $funGame) }}" class="min-h-11 inline-flex items-center px-4 text-sm text-[#d4d4d4]">Batal</a>
            </div>
        </form>
    </div>
</x-layouts.app>
