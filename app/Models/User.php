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

#[Fillable(['name', 'email', 'password', 'no_employe', 'role', 'mandor_id', 'stasiun_id', 'shift'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
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
