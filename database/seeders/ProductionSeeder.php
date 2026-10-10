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
            foreach (Badge::catalog() as $badge) {
                Badge::updateOrCreate(['slug' => $badge['slug']], array_intersect_key($badge, array_flip(['name', 'slug', 'description', 'icon'])));
            }
        });
    }
}
