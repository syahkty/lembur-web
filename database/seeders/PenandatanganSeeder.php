<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenandatanganSeeder extends Seeder
{
    public function run(): void
    {
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
