<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'no_employe',
    'role',
    'mandor_id',
    'stasiun_id',
    'shift',
    'tanda_tangan',
    'gaji_pokok',
    'status_pernikahan',
    'jatah_beras_kg',
    'harga_beras_per_kg',
    'tarif_lembur_per_jam',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = [
        'upah_sejam',
        'tunjangan_beras',
        'total_dasar_upah',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gaji_pokok' => 'float',
            'jatah_beras_kg' => 'float',
            'harga_beras_per_kg' => 'float',
            'tarif_lembur_per_jam' => 'float',
        ];
    }

    /**
     * Hitung total nominal tunjangan catu beras bulanan (Jatah Kg x Harga Beras/Kg)
     */
    public function getTunjanganBerasAttribute(): float
    {
        $kg = (float) ($this->jatah_beras_kg ?? 0);
        $harga = (float) ($this->harga_beras_per_kg ?? 0);
        return round($kg * $harga, 2);
    }

    /**
     * Total Dasar Upah Sebulan (Gaji Pokok + Tunjangan Catu Beras) sesuai standar PT Letawa
     */
    public function getTotalDasarUpahAttribute(): float
    {
        $gaji = (float) ($this->gaji_pokok ?? 0);
        return round($gaji + $this->tunjangan_beras, 2);
    }

    /**
     * Hitung tarif upah lembur per jam (standar PP 35/2021: 1/173 x (Gaji Pokok + Tunjangan Catu Beras))
     */
    public function getUpahSejamAttribute(): float
    {
        if (!empty($this->tarif_lembur_per_jam) && (float) $this->tarif_lembur_per_jam > 0) {
            return (float) $this->tarif_lembur_per_jam;
        }

        $dasarUpah = $this->total_dasar_upah;
        if ($dasarUpah > 0) {
            return round($dasarUpah / 173, 2);
        }

        return 0.0;
    }

    /**
     * Stasiun tempat karyawan bertugas
     */
    public function stasiun(): BelongsTo
    {
        return $this->belongsTo(Stasiun::class, 'stasiun_id');
    }

    /**
     * Mandor yang membawahi karyawan ini
     */
    public function mandorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mandor_id');
    }

    /**
     * Karyawan yang dibawahi (jika user ini mandor)
     */
    public function bawahanList(): HasMany
    {
        return $this->hasMany(User::class, 'mandor_id');
    }

    /**
     * Lembur milik karyawan
     */
    public function lemburs(): HasMany
    {
        return $this->hasMany(Lembur::class, 'user_id');
    }

    /**
     * SPL yang dibuat oleh mandor
     */
    public function splDibuat(): HasMany
    {
        return $this->hasMany(Lembur::class, 'mandor_id');
    }
}
