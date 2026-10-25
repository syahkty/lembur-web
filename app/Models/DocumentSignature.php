<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends Model
{
    protected $fillable = [
        'user_id',
        'tanda_tangan',
        'tanggal',
        'jenis_dokumen',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * User pemilik tanda tangan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ambil tanda tangan terbaru user untuk tanggal tertentu
     */
    public static function getForUser(int $userId, string $tanggal, ?string $jenis = null): ?self
    {
        return self::where('user_id', $userId)
            ->where(function ($q) use ($tanggal, $jenis) {
                $q->where('tanggal', $tanggal);
                if ($jenis) {
                    $q->where('jenis_dokumen', $jenis);
                }
            })
            ->latest()
            ->first();
    }

    /**
     * Ambil TTD permanen (tanggal = null) sebagai fallback
     */
    public static function getPermanen(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereNull('tanggal')
            ->latest()
            ->first();
    }
}
