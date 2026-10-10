<?php

namespace App\Console\Commands;

use App\Enums\Handicap;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('bozzbill:create-admin
    {--name= : Nama pengurus pertama}
    {--phone= : Nomor WhatsApp pengurus pertama}')]
#[Description('Membuat akun pengurus pertama tanpa menyimpan password di riwayat terminal')]
class CreateAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan perintah ini secara interaktif agar password dapat dimasukkan dengan aman.');

            return self::FAILURE;
        }

        if (User::where('role', UserRole::Admin)->exists()) {
            $this->error('Akun pengurus sudah ada. Perintah ini hanya untuk pengurus pertama.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?? $this->ask('Nama pengurus')));
        $phone = preg_replace('/[^\d]/', '', (string) ($this->option('phone') ?? $this->ask('Nomor WhatsApp')));

        if (str_starts_with($phone, '62')) {
            $phone = '0'.substr($phone, 2);
        }

        $identityValidator = Validator::make([
            'name' => $name,
            'phone' => $phone,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', 'unique:users,phone'],
        ], [
            'name.required' => 'Nama pengurus wajib diisi.',
            'name.max' => 'Nama pengurus maksimal 255 karakter.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar. Akun yang ada tidak akan diubah.',
        ]);

        if ($identityValidator->fails()) {
            foreach ($identityValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $passwordValidator = Validator::make([
            'password' => $this->secret('Password admin (minimal 8 karakter)', false),
            'password_confirmation' => $this->secret('Ulangi password admin', false),
        ], [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password admin wajib diisi.',
            'password.min' => 'Password admin minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => Hash::make($passwordValidator->validated()['password']),
            'handicap' => Handicap::ThreeN,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info('Akun pengurus pertama berhasil dibuat. Silakan masuk menggunakan nomor WhatsApp Anda.');

        return self::SUCCESS;
    }
}
