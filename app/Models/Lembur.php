<?php

namespace App\Models;

use Carbon\Carbon;
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
        'jam_mulai_2',
        'jam_selesai_2',
        'keterangan',
        'keterangan_2',
        'keperluan',
        'status',
        'kategori',
        'alasan_revisi',
        'tanda_tangan',
        'foto_dokumentasi',
        'jam_lembur_dibayar',
        'upah_lembur',
    ];

    protected $casts = [
        'jam_lembur_dibayar' => 'float',
        'upah_lembur' => 'float',
    ];

    protected $appends = [
        'durasi_jam',
        'durasi_sesi_1',
        'durasi_sesi_2',
        'has_sesi_2',
        'jam_dibayar_calc',
        'upah_lembur_calc',
        'hari_nama',
    ];

    /**
     * Nama hari dalam bahasa Indonesia (Senin - Minggu)
     */
    public function getHariNamaAttribute(): string
    {
        $days = [
            Carbon::SUNDAY => 'Minggu',
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
        ];
        return $days[Carbon::parse($this->tanggal)->dayOfWeek] ?? '';
    }

    /**
     * Hitung jam lembur dibayar secara eksplisit (PP 35/2021)
     */
    public function hitungJamDibayar(): float
    {
        return $this->jam_dibayar_calc;
    }

    /**
     * Hitung upah lembur rupiah secara eksplisit
     */
    public function hitungUpahLembur(): float
    {
        return $this->upah_lembur_calc;
    }

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

    /**
     * Hitung selisih jam lembur dalam desimal (mendukung lembur lewat tengah malam)
     */
    public static function hitungSelisihJam(?string $mulai, ?string $selesai): float
    {
        if (!$mulai || !$selesai) {
            return 0.0;
        }

        [$h1, $m1] = array_map('intval', explode(':', $mulai));
        [$h2, $m2] = array_map('intval', explode(':', $selesai));

        $diff = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
        if ($diff < 0) {
            $diff += 24 * 60; // Lewat tengah malam (contoh: 21:00 s.d. 02:00 = 5 jam)
        }

        return round($diff / 60, 2);
    }

    /**
     * Durasi Sesi 1 lembur
     */
    public function getDurasiSesi1Attribute(): float
    {
        return self::hitungSelisihJam($this->jam_mulai, $this->jam_selesai);
    }

    /**
     * Durasi Sesi 2 lembur (jika ada)
     */
    public function getDurasiSesi2Attribute(): float
    {
        return self::hitungSelisihJam($this->jam_mulai_2, $this->jam_selesai_2);
    }

    /**
     * Apakah memiliki lembur sesi ke-2
     */
    public function getHasSesi2Attribute(): bool
    {
        return !empty($this->jam_mulai_2) && !empty($this->jam_selesai_2);
    }

    /**
     * Total durasi nyata lembur per hari (akumulasi Sesi 1 + Sesi 2)
     */
    public function getDurasiJamAttribute(): float
    {
        $sesi1 = $this->durasi_sesi_1;
        $sesi2 = $this->durasi_sesi_2;

        return round($sesi1 + $sesi2, 2);
    }

    /**
     * Hitung jam dibayar ekuivalen menurut PP No. 35 Tahun 2021
     * - Hari kerja (Senin - Sabtu):
     *   1 jam pertama = 1.5x
     *   Jam ke-2 dan seterusnya = 2.0x
     * - Hari libur resmi / istirahat mingguan (Minggu):
     *   7 jam pertama = 2.0x
     *   Jam ke-8 = 3.0x
     *   Jam ke-9 dst = 4.0x
     */
    public function getJamDibayarCalcAttribute(): float
    {
        if ($this->jam_lembur_dibayar !== null && (float) $this->jam_lembur_dibayar > 0) {
            return (float) $this->jam_lembur_dibayar;
        }

        $durasi = $this->durasi_jam;
        if ($durasi <= 0) {
            return 0.0;
        }

        $tgl = Carbon::parse($this->tanggal);
        $isMinggu = ($tgl->dayOfWeek === Carbon::SUNDAY);

        if ($isMinggu) {
            if ($durasi <= 7) {
                return round($durasi * 2.0, 2);
            } elseif ($durasi <= 8) {
                return round((7 * 2.0) + (($durasi - 7) * 3.0), 2);
            } else {
                return round((7 * 2.0) + (1 * 3.0) + (($durasi - 8) * 4.0), 2);
            }
        }

        // Hari kerja biasa (Senin - Sabtu, 6 hari kerja):
        if ($durasi <= 1.0) {
            return round($durasi * 1.5, 2);
        } else {
            return round(1.5 + (($durasi - 1.0) * 2.0), 2);
        }
    }

    /**
     * Hitung estimasi upah lembur rupiah (Jam Dibayar x Upah Sejam)
     */
    public function getUpahLemburCalcAttribute(): float
    {
        if ($this->upah_lembur !== null && (float) $this->upah_lembur > 0) {
            return (float) $this->upah_lembur;
        }

        $jamDibayar = $this->jam_dibayar_calc;
        $upahSejam = $this->user ? $this->user->upah_sejam : 0.0;

        return round($jamDibayar * $upahSejam, 0);
    }
}