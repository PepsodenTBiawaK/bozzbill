<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeleteRoster
{
    public function handle(User $actor, User $member, string $confirmedName): void
    {
        DB::transaction(function () use ($actor, $member, $confirmedName): void {
            $accounts = User::query()->whereIn('id', [$actor->id, $member->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $operator = $accounts->get($actor->id);
            $target = $accounts->get($member->id);
            abort_unless($operator?->isSuperadmin(), 403);
            abort_if($target === null, 404);
            abort_if($operator->is($target) || $target->isSuperadmin(), 403, 'Akun Superadmin tidak dapat dihapus.');
            if ($target->name !== $confirmedName) {
                throw ValidationException::withMessages(['confirmation_name' => 'Nama konfirmasi tidak sesuai. Periksa kembali anggota yang akan dihapus.']);
            }

            $target->forceFill([
                'phone' => null,
                'email' => null,
                'password' => null,
                'is_active' => false,
                'activation_code_hash' => null,
                'activation_code_expires_at' => null,
            ])->save();
            $target->revokeLoginSessions();
            $target->delete();

            DB::table('account_changes')->insert([
                'user_id' => $target->id,
                'changed_by' => $operator->id,
                'field' => 'deleted_at',
                'old_value' => null,
                'new_value' => $target->deleted_at->toIso8601String(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Log::channel('security')->info('roster.deleted', ['actor_id' => $actor->id, 'subjects' => ['user' => $member->id]]);
    }
}
