<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembur extends Model
{
    protected $fillable = [
        'user_id',
        'mandor_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'keterangan',
        'keperluan',
        'status',
        'kategori',
        'alasan_revisi',
    ];

    /**
     * Karyawan yang dilembur-kan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mandor yang membuat SPL
     */
    public function mandor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mandor_id');
    }
}