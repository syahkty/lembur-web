<?php

namespace Database\Seeders;

use App\Models\Stasiun;
use Illuminate\Database\Seeder;

class StasiunSeeder extends Seeder
{
    /**
     * Seed 8 stasiun pabrik pengolahan CPO
     */
    public function run(): void
    {
        $stasions = [
            ['nama' => 'Sterilizer (Perebusan)', 'kode' => 'STR'],
            ['nama' => 'Chainman', 'kode' => 'CHN'],
            ['nama' => 'Thresher (Penebahan)', 'kode' => 'THR'],
            ['nama' => 'Pressing (Pengempaan)', 'kode' => 'PRS'],
            ['nama' => 'Clarification (Klarifikasi)', 'kode' => 'CLR'],
            ['nama' => 'Kernel (Inti Sawit)', 'kode' => 'KRN'],
            ['nama' => 'Boiler', 'kode' => 'BLR'],
            ['nama' => 'Power House', 'kode' => 'PWH'],
        ];

        foreach ($stasions as $stasiun) {
            Stasiun::updateOrCreate(
                ['kode' => $stasiun['kode']],
                $stasiun
            );
        }
    }
}
