<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    /**
     * Seed production master data without demo accounts or community activity.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                [
                    'name' => 'Sharpshooter',
                    'slug' => 'sharpshooter',
                    'description' => 'Akurasi drilling latihan mandiri mencapai di atas 80%',
                    'icon' => 'target',
                ],
                [
                    'name' => 'Electric Form',
                    'slug' => 'electric-form',
                    'description' => 'Aktif bertanding di 4 event Fun Game berturut-turut',
                    'icon' => 'zap',
                ],
                [
                    'name' => 'Champion',
                    'slug' => 'champion',
                    'description' => 'Meraih Juara 1 pada event Fun Game resmi Komunitas Bozz',
                    'icon' => 'trophy',
                ],
                [
                    'name' => 'Giant Killer',
                    'slug' => 'giant-killer',
                    'description' => 'Menang dalam pertandingan melawan pemain dengan HC lebih tinggi',
                    'icon' => 'swords',
                ],
                [
                    'name' => 'Drill Master',
                    'slug' => 'drill-master',
                    'description' => 'Mencatat lebih dari 50x percobaan latihan drilling mandiri',
                    'icon' => 'flame',
                ],
            ] as $badge) {
                Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
            }
        });
    }
}
