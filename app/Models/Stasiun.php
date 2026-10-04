<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stasiun extends Model
{
    protected $table = 'stasions';

    protected $fillable = ['nama', 'kode'];

    /**
     * Karyawan yang bertugas di stasiun ini
     */
    public function karyawan(): HasMany
    {
        return $this->hasMany(User::class, 'stasiun_id');
    }
}
