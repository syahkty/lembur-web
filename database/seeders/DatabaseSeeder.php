<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Stasiun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StasiunSeeder::class);
        $this->call(PenandatanganSeeder::class);
        $stations = Stasiun::all();

        $hasFaker = class_exists('Faker\Factory');
        $faker = $hasFaker ? \Faker\Factory::create('id_ID') : null;

        // 1. Buat Admin
        User::firstOrCreate(
            ['email' => 'admin@letawa.com'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Buat 2 Mandor
        $mandor1 = User::updateOrCreate(
            ['email' => 'mandora@letawa.com'],
            [
                'name' => 'Bapak Mandor A',
                'password' => Hash::make('password'),
                'role' => 'mandor',
                'shift' => 1,
            ]
        );

        $mandor2 = User::updateOrCreate(
            ['email' => 'mandorb@letawa.com'],
            [
                'name' => 'Bapak Mandor B',
                'password' => Hash::make('password'),
                'role' => 'mandor',
                'shift' => 2,
            ]
        );

        $sampleCodes = ['214771', '300131', '1362951', '2223821', '1302951', '914791', '9505811', '1306771', '1156521', '9904091', '1303101', '1262111', '852281', '1221381', '9905261', '202111', '1302991', '113821', '938301', '5581'];

        // 3. Buat 10 Karyawan untuk Mandor A (Shift 1)
        for ($i = 1; $i <= 10; $i++) {
            $stasiun = $stations[($i - 1) % $stations->count()];
            User::updateOrCreate(
                ['email' => "karyawan.a{$i}@letawa.com"],
                [
                    'name' => $faker ? $faker->name : "Karyawan Shift 1 #{$i}",
                    'password' => Hash::make('password'),
                    'role' => 'karyawan',
                    'mandor_id' => $mandor1->id,
                    'shift' => 1,
                    'stasiun_id' => $stasiun->id,
                    'no_employe' => $sampleCodes[$i - 1] ?? (string)(200000 + $i),
                ]
            );
        }

        // 4. Buat 10 Karyawan untuk Mandor B (Shift 2)
        for ($i = 1; $i <= 10; $i++) {
            $stasiun = $stations[($i - 1) % $stations->count()];
            User::updateOrCreate(
                ['email' => "karyawan.b{$i}@letawa.com"],
                [
                    'name' => $faker ? $faker->name : "Karyawan Shift 2 #{$i}",
                    'password' => Hash::make('password'),
                    'role' => 'karyawan',
                    'mandor_id' => $mandor2->id,
                    'shift' => 2,
                    'stasiun_id' => $stasiun->id,
                    'no_employe' => $sampleCodes[$i + 9] ?? (string)(300000 + $i),
                ]
            );
        }
    }
}