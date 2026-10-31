<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\Lembur;
use App\Models\Stasiun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controller untuk role penandatangan dokumen:
 * asisten, askep, ktu, administratur, hrga
 *
 * Hak akses: hanya lihat SPL & LRLK, dan bisa tanda tangan digital di dokumen cetak.
 */
class DokumenController extends Controller
{
    /**
     * Role-role yang diizinkan mengakses halaman ini
     */
    public const ALLOWED_ROLES = ['asisten', 'askep', 'ktu', 'administratur', 'hrga'];

    /**
     * Mapping jabatan untuk tampilan dokumen cetak
     */
    public const JABATAN_MAP = [
        'asisten'       => 'Asisten',
        'askep'         => 'Asisten Kepala',
        'ktu'           => 'KTU',
        'administratur' => 'Administratur',
        'hrga'          => 'Kabag HRGA',
    ];

    /**
     * Role yang menandatangani masing-masing kertas dokumen
     */
    public const SPL_ROLES  = ['asisten', 'askep'];
    public const LRLK_ROLES = ['hrga', 'ktu', 'administratur'];

    protected function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, self::ALLOWED_ROLES)) {
            abort(403, 'Akses ditolak.');
        }
    }

    /**
     * Halaman utama: tampilkan SPL dan LRLK untuk tanggal tertentu
     */
    public function index(Request $request)
    {
        $this->checkAccess();

        $user = auth()->user();
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());

        // SPL data
        $lemburs = Lembur::with(['user.stasiun', 'mandor'])
            ->where('tanggal', $tanggal)
            ->orderBy('id')
            ->get();

        // LRLK data (sama seperti LemburController::lrlk)
        $totalJamDibayar = 0;
        $lrlkItems = $lemburs->map(function ($l, $index) use (&$totalJamDibayar) {
            $isLembur = ($l->kategori === 'lembur' || $l->status === 'disetujui' || $l->status === 'pending');
            $durasiFisik = $isLembur ? $l->durasi_jam : 0;
            $jamTeks = $isLembur
                ? (fmod($durasiFisik, 1) == 0 ? (string)(int)$durasiFisik : (string)$durasiFisik)
                : '-';

            if ($isLembur) {
                $totalJamDibayar += $durasiFisik;
            }

            $tglObj = Carbon::parse($l->tanggal);
            $tglFormatted = $tglObj->format('j/n/y');

            $pekerjaan = $l->keterangan ?: ($isLembur ? 'Lanjut proses' : ucfirst($l->kategori ?: 'Tidak Lembur'));
            if ($l->keterangan_2 && $l->keterangan_2 !== $l->keterangan) {
                $pekerjaan .= ' & ' . $l->keterangan_2;
            }

            return [
                'id'              => $l->id,
                'no'              => $index + 1,
                'no_employe'      => $l->user->no_employe ?? '-',
                'nama'            => $l->user->name ?? '-',
                'dept'            => 'Pabrik',
                'tgl'             => $tglFormatted,
                'jenis_pekerjaan' => $pekerjaan,
                'jam_dibayar'     => $jamTeks,
                'jumlah_rp'       => '',
                'hasil_kerja'     => $l->foto_dokumentasi ? 'Ada Dokumentasi' : '-',
            ];
        });

        // Ambil master tanda tangan tersimpan di akun user (sebagai template agar tidak perlu gambar ulang)
        $savedSignature = $this->getMasterSignature($user->id);

        // Ambil tanda tangan harian user ini khusus untuk tanggal terpilih (terpisah SPL & LRLK)
        $mySplSignature = $this->getDailySignature($user->id, $tanggal, 'spl');
        $myLrlkSignature = $this->getDailySignature($user->id, $tanggal, 'lrlk');

        // Ambil semua tanda tangan penandatangan untuk dokumen SPL & LRLK pada tanggal ini
        $docSignaturesSpl = $this->getAllDocSignatures($tanggal, 'spl');
        $docSignaturesLrlk = $this->getAllDocSignatures($tanggal, 'lrlk');

        return Inertia::render('Dokumen/Index', [
            'lemburs'           => $lemburs,
            'lrlkItems'         => $lrlkItems,
            'tanggal'           => $tanggal,
            'totalJamDibayar'   => $totalJamDibayar,
            'userRole'          => $user->role,
            'userName'          => $user->name,
            'userJabatan'       => self::JABATAN_MAP[$user->role] ?? $user->role,
            'savedSignature'    => $savedSignature,
            'mySplSignature'    => $mySplSignature,
            'myLrlkSignature'   => $myLrlkSignature,
            'docSignaturesSpl'  => $docSignaturesSpl,
            'docSignaturesLrlk' => $docSignaturesLrlk,
            'docSignatures'     => $docSignaturesSpl,
        ]);
    }

    /**
     * Simpan tanda tangan digital penandatangan untuk tanggal & jenis dokumen tertentu (spl / lrlk)
     */
    public function simpanTandaTangan(Request $request)
    {
        $this->checkAccess();

        $user = auth()->user();

        $request->validate([
            'tanda_tangan'    => 'required|string|max:2000000',
            'tanggal'         => 'required|date',
            'jenis_dokumen'   => 'required|in:spl,lrlk',
            'simpan_permanen' => 'nullable|boolean',
        ]);

        $tanggal = $request->tanggal;
        // Sesuaikan dokumen dengan wewenang jabatan penandatangan:
        // Asisten & Askep -> SPL | Kabag HRGA, KTU, Administratur -> LRLK
        $jenisDokumen = in_array($user->role, self::SPL_ROLES) ? 'spl' : 'lrlk';
        $ttdData = $request->tanda_tangan;

        // Simpan/update TTD khusus untuk tanggal & jenis dokumen ini ('spl' atau 'lrlk')
        DocumentSignature::updateOrCreate(
            [
                'user_id'       => $user->id,
                'tanggal'       => $tanggal,
                'jenis_dokumen' => $jenisDokumen,
            ],
            [
                'tanda_tangan' => $ttdData,
            ]
        );

        // Jika simpan permanen dicentang, simpan sebagai master template (tanggal = null)
        // agar ke depannya cukup 1x klik saat menyetujui SPL/LRLK hari berikutnya tanpa menggambar ulang
        if ($request->boolean('simpan_permanen')) {
            DocumentSignature::updateOrCreate(
                [
                    'user_id'       => $user->id,
                    'tanggal'       => null,
                    'jenis_dokumen' => null,
                ],
                [
                    'tanda_tangan' => $ttdData,
                ]
            );
            $user->update(['tanda_tangan' => $ttdData]);
        }

        $labelDok = strtoupper($jenisDokumen);
        return redirect()->back()->with('success', "Dokumen {$labelDok} tanggal {$tanggal} berhasil disetujui & ditandatangani.");
    }

    /**
     * Batalkan persetujuan / tanda tangan pada tanggal & jenis dokumen tertentu
     */
    public function batalTandaTangan(Request $request)
    {
        $this->checkAccess();

        $user = auth()->user();

        $request->validate([
            'tanggal'       => 'required|date',
            'jenis_dokumen' => 'required|in:spl,lrlk',
        ]);

        DocumentSignature::where('user_id', $user->id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('jenis_dokumen', $request->jenis_dokumen)
            ->delete();

        $labelDok = strtoupper($request->jenis_dokumen);
        return redirect()->back()->with('success', "Tanda tangan {$labelDok} untuk tanggal {$request->tanggal} berhasil dibatalkan.");
    }

    /**
     * Ambil master tanda tangan tersimpan di akun user (hanya untuk template di modal)
     */
    private function getMasterSignature(int $userId): ?string
    {
        $permanen = DocumentSignature::where('user_id', $userId)
            ->whereNull('tanggal')
            ->whereNull('jenis_dokumen')
            ->latest()
            ->first();

        if ($permanen) {
            return $permanen->tanda_tangan;
        }

        return \App\Models\User::find($userId)?->tanda_tangan;
    }

    /**
     * Ambil tanda tangan harian user khusus untuk tanggal & jenis dokumen ('spl' atau 'lrlk').
     * TIDAK fallback ke tanda tangan permanen agar setiap hari & setiap dokumen wajib disetujui manual.
     */
    private function getDailySignature(int $userId, string $tanggal, string $jenisDokumen): ?string
    {
        $sig = DocumentSignature::where('user_id', $userId)
            ->whereDate('tanggal', $tanggal)
            ->where('jenis_dokumen', $jenisDokumen)
            ->first();

        return $sig?->tanda_tangan;
    }

    /**
     * Ambil semua tanda tangan penandatangan untuk dokumen ('spl' atau 'lrlk') pada tanggal tertentu
     * Digunakan untuk tampilan di Print.vue & PrintLrlk.vue
     */
    private function getAllDocSignatures(string $tanggal, string $jenisDokumen): array
    {
        $result = [];
        $targetRoles = $jenisDokumen === 'spl' ? self::SPL_ROLES : self::LRLK_ROLES;

        foreach ($targetRoles as $role) {
            // Cari user dengan role ini
            $userWithRole = \App\Models\User::where('role', $role)->first();
            if (!$userWithRole) continue;

            // Cari TTD khusus untuk tanggal & jenis dokumen ini saja (tanpa fallback permanen)
            $ttd = $this->getDailySignature($userWithRole->id, $tanggal, $jenisDokumen);

            $result[$role] = [
                'nama'         => $userWithRole->name,
                'jabatan'      => self::JABATAN_MAP[$role] ?? $role,
                'tanda_tangan' => $ttd,
            ];
        }

        return $result;
    }

    /**
     * Halaman print SPL khusus penandatangan (dengan tanda tangan SPL tanggal tersebut)
     */
    public function printSpl(Request $request)
    {
        $this->checkAccess();

        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $user = auth()->user();

        $lemburs = Lembur::with(['user.stasiun', 'mandor'])
            ->where('tanggal', $tanggal)
            ->orderBy('id')
            ->get();

        $docSignatures = $this->getAllDocSignatures($tanggal, 'spl');

        return Inertia::render('Lembur/Print', [
            'lemburs'        => $lemburs,
            'tanggal'        => $tanggal,
            'userRole'       => $user->role,
            'docSignatures'  => $docSignatures,
            'previewMode'    => true,
        ]);
    }

    /**
     * Halaman print LRLK khusus penandatangan
     */
    public function printLrlk(Request $request)
    {
        $this->checkAccess();

        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $user = auth()->user();

        $lemburs = Lembur::with(['user.stasiun', 'mandor'])
            ->where('tanggal', $tanggal)
            ->orderBy('id')
            ->get();

        $totalJamDibayar = 0;
        $items = $lemburs->map(function ($l, $index) use (&$totalJamDibayar) {
            $isLembur = ($l->kategori === 'lembur' || $l->status === 'disetujui' || $l->status === 'pending');
            $durasiFisik = $isLembur ? $l->durasi_jam : 0;
            $jamTeks = $isLembur
                ? (fmod($durasiFisik, 1) == 0 ? (string)(int)$durasiFisik : (string)$durasiFisik)
                : '-';

            if ($isLembur) $totalJamDibayar += $durasiFisik;

            $tglObj = Carbon::parse($l->tanggal);
            $pekerjaan = $l->keterangan ?: ($isLembur ? 'lanjut proses' : ucfirst($l->kategori ?: 'cuti'));
            if ($l->keterangan_2 && $l->keterangan_2 !== $l->keterangan) {
                $pekerjaan .= ' & ' . $l->keterangan_2;
            }

            return [
                'id'              => $l->id,
                'no'              => $index + 1,
                'no_employe'      => $l->user->no_employe ?? '-',
                'nama'            => $l->user->name ?? '-',
                'dept'            => 'Pabrik',
                'tgl'             => $tglObj->format('j/n/y'),
                'jenis_pekerjaan' => $pekerjaan,
                'jam_dibayar'     => $jamTeks,
                'jumlah_rp'       => '',
                'hasil_kerja'     => '',
            ];
        });

        $docSignatures = $this->getAllDocSignatures($tanggal, 'lrlk');

        return Inertia::render('Lembur/PrintLrlk', [
            'items'          => $items,
            'tanggal'        => $tanggal,
            'totalJamDibayar' => $totalJamDibayar,
            'totalUpahLembur' => 0,
            'userRole'       => $user->role,
            'docSignatures'  => $docSignatures,
            'previewMode'    => true,
        ]);
    }
}
