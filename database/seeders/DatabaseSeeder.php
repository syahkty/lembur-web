<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Stasiun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StasiunSeeder::class);
        $stations = Stasiun::all();

        $faker = Faker::create('id_ID');

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
                    'name' => $faker->name,
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
                    'name' => $faker->name,
                    'password' => Hash::make('password'),
                    'role' => 'karyawan',
                    'mandor_id' => $mandor2->id,
                    'shift' => 2,
                    'stasiun_id' => $stasiun->id,
                    'no_employe' => $sampleCodes[$i + 9] ?? (string)(300000 + $i),
                ]
            );
        }

        // 5. Buat 5 Akun Penandatangan Dokumen
        $signers = [
            ['name' => 'Bapak Asisten', 'email' => 'asisten@letawa.com', 'role' => 'asisten', 'no_employe' => '101001'],
            ['name' => 'Bapak Asisten Kepala (Askep)', 'email' => 'askep@letawa.com', 'role' => 'askep', 'no_employe' => '101002'],
            ['name' => 'Bapak KTU (Kepala Tata Usaha)', 'email' => 'ktu@letawa.com', 'role' => 'ktu', 'no_employe' => '101003'],
            ['name' => 'Bapak Administratur', 'email' => 'administratur@letawa.com', 'role' => 'administratur', 'no_employe' => '101004'],
            ['name' => 'Staff HRGA', 'email' => 'hrga@letawa.com', 'role' => 'hrga', 'no_employe' => '101005'],
        ];

        foreach ($signers as $signer) {
            User::updateOrCreate(
                ['email' => $signer['email']],
                [
                    'name' => $signer['name'],
                    'password' => Hash::make('password'),
                    'role' => $signer['role'],
                    'no_employe' => $signer['no_employe'],
                    'stasiun_id' => null,
                    'shift' => null,
                    'mandor_id' => null,
                    'gaji_pokok' => 0,
                    'status_pernikahan' => 'TK',
                    'jatah_beras_kg' => 0,
                    'harga_beras_per_kg' => 0,
                    'tarif_lembur_per_jam' => 0,
                ]
            );
        }
    }
}