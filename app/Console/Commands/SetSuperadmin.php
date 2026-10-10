<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Signature('bozzbill:set-superadmin {user : ID akun pengurus yang sudah ada}')]
#[Description('Menetapkan Superadmin pertama tanpa mengganti identitas atau password')]
class SetSuperadmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! ctype_digit((string) $this->argument('user')) || (int) $this->argument('user') < 1) {
            $this->error('Gunakan ID pengurus yang valid.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function (): void {
                $admins = User::query()->whereIn('role', [UserRole::Admin, UserRole::Superadmin])
                    ->orderBy('id')->lockForUpdate()->get();
                $target = $admins->firstWhere('id', (int) $this->argument('user'));

                if (! $target || ! $target->is_active) {
                    throw new RuntimeException('Pilih ID pengurus aktif yang sudah terdaftar.');
                }
                if ($target->isSuperadmin()) {
                    return;
                }
                if ($admins->contains(fn (User $admin): bool => $admin->isSuperadmin())) {
                    throw new RuntimeException('Superadmin sudah ada. Akun pengurus lain tidak akan diubah.');
                }

                DB::table('account_changes')->insert([
                    'user_id' => $target->id, 'changed_by' => null, 'field' => 'role',
                    'old_value' => $target->role->value, 'new_value' => UserRole::Superadmin->value,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $target->update(['role' => UserRole::Superadmin]);
                $target->revokeLoginSessions();
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Superadmin siap. Identitas, password, HC, dan histori pemain tetap tersimpan.');

        return self::SUCCESS;
    }
}
