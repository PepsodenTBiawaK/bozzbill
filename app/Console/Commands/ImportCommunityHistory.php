<?php

namespace App\Console\Commands;

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\Badge;
use App\Models\FunGame;
use App\Models\HcHistory;
use App\Models\MatchLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

#[Signature('bozzbill:import-history {file : JSON hasil pemeriksaan Excel}
    {--fadil= : ID akun Fadil yang sudah ada}
    {--apply : Simpan hasil; tanpa opsi ini hanya pratinjau dan rollback}')]
#[Description('Mengimpor arsip komunitas secara atomik tanpa menggandakan akun atau pertandingan')]
class ImportCommunityHistory extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $path = (string) $this->argument('file');
            if (! is_file($path) || ! is_readable($path) || filesize($path) > 5_000_000) {
                throw new RuntimeException('File impor tidak tersedia atau terlalu besar.');
            }
            $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new RuntimeException('Isi file harus berupa objek JSON.');
            }
            $this->validateArchive($data);
            $counts = Cache::lock('community-history:import', 120)->block(5, function () use ($data): array {
                DB::beginTransaction();
                try {
                    $counts = $this->importArchive($data);
                    if ($this->option('apply')) {
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }

                    return $counts;
                } catch (Throwable $exception) {
                    DB::rollBack();
                    throw $exception;
                }
            });
        } catch (Throwable $exception) {
            $message = $exception instanceof ValidationException
                ? implode(' ', $exception->validator->errors()->all())
                : ($exception instanceof RuntimeException && ! $exception instanceof QueryException ? $exception->getMessage() : 'Impor gagal karena konflik atau kesalahan data.');
            $this->error($message);
            $this->error('Tidak ada perubahan data impor yang disimpan.');

            return self::FAILURE;
        }

        $this->info($this->option('apply') ? 'Impor tersimpan.' : 'Pratinjau selesai; tidak ada perubahan database yang disimpan.');
        $this->line(json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $data */
    private function validateArchive(array $data): void
    {
        Validator::make($data, [
            'source' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9-]+$/'],
            'members' => ['required', 'array', 'min:1', 'max:1000'],
            'members.*.name' => ['required', 'string', 'max:100', 'distinct:strict'],
            'members.*.phone' => ['nullable', 'string', 'regex:/^08[0-9]{8,13}$/'],
            'members.*.handicap' => ['required', Rule::enum(Handicap::class)],
            'members.*.notes' => ['nullable', 'string', 'max:2000'],
            'events' => ['present', 'array', 'max:1000'],
            'events.*.code' => ['required', 'string', 'max:30', 'distinct:strict', 'regex:/^[A-Za-z0-9-]+$/'],
            'events.*.title' => ['required', 'string', 'max:150'],
            'events.*.event_date' => ['required', 'date_format:Y-m-d'],
            'events.*.format' => ['required', Rule::in(['single', 'double'])],
            'events.*.status' => ['required', Rule::in(['completed', 'cancelled'])],
            'events.*.notes' => ['nullable', 'string', 'max:5000'],
            'events.*.podium' => ['present', 'array', 'max:3'],
            'events.*.podium.*.position' => ['required', 'integer', 'between:1,3'],
            'events.*.podium.*.names' => ['required', 'array', 'min:1', 'max:2'],
            'events.*.podium.*.names.*' => ['required', 'string'],
            'events.*.matches' => ['present', 'array', 'max:1000'],
            'events.*.matches.*.key' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'events.*.matches.*.player1' => ['required', 'string'],
            'events.*.matches.*.player2' => ['required', 'string'],
            'events.*.matches.*.winner' => ['required', 'string'],
            'events.*.matches.*.player1_hc' => ['required', Rule::enum(Handicap::class)],
            'events.*.matches.*.player2_hc' => ['required', Rule::enum(Handicap::class)],
            'hc_changes' => ['present', 'array'],
            'hc_changes.*.key' => ['required', 'string', 'max:30', 'distinct:strict', 'regex:/^[A-Za-z0-9-]+$/'],
            'hc_changes.*.name' => ['required', 'string'],
            'hc_changes.*.old_hc' => ['required', Rule::enum(Handicap::class)],
            'hc_changes.*.new_hc' => ['required', Rule::enum(Handicap::class)],
            'hc_changes.*.effective_date' => ['required', 'date_format:Y-m-d'],
            'hc_changes.*.reason' => ['required', 'string', 'max:2000'],
        ])->validate();

        $names = array_column($data['members'], 'name');
        $phones = array_values(array_filter(array_column($data['members'], 'phone')));
        if (count($phones) !== count(array_unique($phones))) {
            throw new RuntimeException('Ada nomor WhatsApp yang digunakan lebih dari satu nama dalam file.');
        }
        foreach ($data['events'] as $event) {
            $positions = [];
            $participants = [];
            if ($event['status'] === 'cancelled' && ($event['podium'] !== [] || $event['matches'] !== [])) {
                throw new RuntimeException('Event libur tidak boleh memiliki podium atau pertandingan.');
            }
            if ($event['format'] === 'double' && $event['matches'] !== []) {
                throw new RuntimeException('Arsip double hanya menerima podium pasangan, bukan pertandingan single.');
            }
            foreach ($event['podium'] as $place) {
                if (in_array($place['position'], $positions, true)
                    || count($place['names']) !== ($event['format'] === 'double' ? 2 : 1)) {
                    throw new RuntimeException('Posisi atau jumlah pemain podium tidak sesuai format event.');
                }
                $positions[] = $place['position'];
                foreach ($place['names'] as $name) {
                    if (! in_array($name, $names, true) || in_array($name, $participants, true)) {
                        throw new RuntimeException('Nama podium tidak dikenal atau menempati lebih dari satu posisi.');
                    }
                    $participants[] = $name;
                }
            }
            $keys = [];
            foreach ($event['matches'] as $match) {
                if (! in_array($match['player1'], $names, true) || ! in_array($match['player2'], $names, true)
                    || $match['player1'] === $match['player2']
                    || ! in_array($match['winner'], [$match['player1'], $match['player2']], true)
                    || in_array($match['key'], $keys, true)) {
                    throw new RuntimeException('Pemain, pemenang, atau identitas pertandingan tidak valid.');
                }
                $keys[] = $match['key'];
            }
        }
        foreach ($data['hc_changes'] as $change) {
            if (! in_array($change['name'], $names, true)) {
                throw new RuntimeException('Nama dalam riwayat HC tidak dikenal.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    private function importArchive(array $data): array
    {
        $owner = User::query()->lockForUpdate()->find((int) $this->option('fadil'));
        if (! $owner || $owner->name !== 'Fadil' || ! $owner->isSuperadmin() || ! $owner->is_active) {
            throw new RuntimeException('Pilih ID akun Fadil aktif yang sudah menjadi Superadmin melalui --fadil.');
        }
        $counts = ['new_members' => 0, 'existing_members' => 0, 'new_events' => 0, 'new_matches' => 0, 'new_hc_records' => 0];
        $users = [];
        $source = $data['source'];
        foreach ($data['members'] as $member) {
            $key = $source.':member:'.sha1($member['name']);
            $user = User::where('source_key', $key)->first();
            $firstImport = $user === null;
            if ($member['name'] === 'Fadil') {
                if ($user && ! $user->is($owner)) {
                    throw new RuntimeException('Pemetaan Fadil menunjuk akun yang berbeda.');
                }
                $user = $owner;
            } elseif (! $user) {
                $user = isset($member['phone']) ? User::where('phone', $member['phone'])->first() : null;
                if ($user && ($user->name !== $member['name'] || ($user->source_key && $user->source_key !== $key))) {
                    throw new RuntimeException('Nomor WA cocok dengan identitas akun lain. Pemetaan perlu diperiksa.');
                }
                if (! $user && User::where('name', $member['name'])->exists()) {
                    throw new RuntimeException('Nama sudah ada tanpa pemetaan yang pasti. Akun tidak akan digandakan.');
                }
            }
            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'name' => $member['name'], 'phone' => $member['phone'] ?? null,
                    'handicap' => $member['handicap'], 'role' => UserRole::Member,
                    'password' => null, 'is_active' => false, 'source_key' => $key,
                ])->save();
                $counts['new_members']++;
            } else {
                $counts['existing_members']++;
                if ($firstImport) {
                    if ($user->source_key && $user->source_key !== $key) {
                        throw new RuntimeException('Akun sudah dipetakan ke sumber lain.');
                    }
                    $user->forceFill(['source_key' => $key, 'handicap' => $member['handicap']])->save();
                }
            }
            $users[$member['name']] = $user;
            $this->createHcRecord($source.':baseline:'.sha1($member['name']), [
                'user_id' => $user->id, 'old_hc' => null, 'new_hc' => $member['handicap'],
                'reason' => 'HC awal saat impor arsip; bukan tanggal penetapan HC historis. '.($member['notes'] ?? ''),
                'changed_by' => $owner->id, 'approved_at' => now(),
            ], $counts);
        }
        if (! isset($users['Fadil'])) {
            throw new RuntimeException('Daftar sumber harus menyertakan Fadil untuk pemetaan akun yang sudah ada.');
        }

        foreach ($data['events'] as $entry) {
            $key = $source.':event:'.$entry['code'];
            $event = FunGame::where('source_key', $key)->first();
            if (! $event) {
                if (FunGame::where('title', $entry['title'])->whereDate('event_date', $entry['event_date'])->exists()) {
                    throw new RuntimeException('Event serupa sudah ada tanpa identitas sumber. Impor dihentikan agar tidak ganda.');
                }
                $podium = collect($entry['podium'])->keyBy('position');
                $event = new FunGame;
                $event->forceFill([
                    'source_key' => $key, 'title' => $entry['title'], 'event_date' => $entry['event_date'],
                    'format' => $entry['format'], 'status' => $entry['status'], 'match_records_complete' => false,
                    'notes' => $entry['notes'] ?? null, 'created_by' => $owner->id,
                    'champion_id' => $entry['format'] === 'single' && $podium->has(1) ? $users[$podium[1]['names'][0]]->id : null,
                    'runner_up_id' => $entry['format'] === 'single' && $podium->has(2) ? $users[$podium[2]['names'][0]]->id : null,
                    'third_place_id' => $entry['format'] === 'single' && $podium->has(3) ? $users[$podium[3]['names'][0]]->id : null,
                ])->save();
                foreach ($entry['podium'] as $place) {
                    foreach ($place['names'] as $name) {
                        $event->podiumPlayers()->attach($users[$name]->id, ['position' => $place['position']]);
                        if ($place['position'] === 1) {
                            $this->awardBadge($users[$name], 'champion', $entry['event_date']);
                        }
                    }
                }
                $counts['new_events']++;
            }
            foreach ($entry['matches'] as $row) {
                $matchKey = $source.':match:'.$entry['code'].':'.$row['key'];
                if (MatchLog::where('source_key', $matchKey)->exists()) {
                    continue;
                }
                $match = new MatchLog;
                $match->forceFill([
                    'source_key' => $matchKey, 'fun_game_id' => $event->id,
                    'player1_id' => $users[$row['player1']]->id, 'player2_id' => $users[$row['player2']]->id,
                    'winner_id' => $users[$row['winner']]->id, 'player1_hc' => $row['player1_hc'], 'player2_hc' => $row['player2_hc'],
                    'player1_score' => null, 'player2_score' => null, 'stage' => 'Babak tidak tercatat',
                    'recorded_by' => $owner->id, 'created_at' => $entry['event_date'].' 00:00:00',
                ])->save();
                if ($match->isGiantKiller()) {
                    $this->awardBadge($users[$row['winner']], 'giant-killer', $entry['event_date']);
                }
                $counts['new_matches']++;
            }
        }
        foreach ($data['hc_changes'] as $change) {
            $this->createHcRecord($source.':hc:'.$change['key'], [
                'user_id' => $users[$change['name']]->id, 'old_hc' => $change['old_hc'], 'new_hc' => $change['new_hc'],
                'reason' => $change['reason'], 'changed_by' => $owner->id, 'approved_at' => $change['effective_date'].' 00:00:00',
            ], $counts);
        }

        foreach ($users as $user) {
            $user->syncAchievementBadges(['champions', 'giant_kills', 'streak']);
        }

        return $counts;
    }

    /** @param array<string, mixed> $attributes @param array<string, int> $counts */
    private function createHcRecord(string $key, array $attributes, array &$counts): void
    {
        if (! HcHistory::where('source_key', $key)->exists()) {
            (new HcHistory)->forceFill(['source_key' => $key, ...$attributes])->save();
            $counts['new_hc_records']++;
        }
    }

    private function awardBadge(User $user, string $slug, string $date): void
    {
        $badge = Badge::where('slug', $slug)->first();
        if ($badge && ! $user->badges()->where('badge_id', $badge->id)->exists()) {
            $user->badges()->attach($badge->id, ['awarded_at' => $date.' 00:00:00']);
        }
    }
}
