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
        'asisten'     => 'Asisten',
        'askep'       => 'Asisten Kepala',
        'ktu'         => 'KTU',
        'administratur' => 'Administratur',
        'hrga'        => 'Staff HRGA',
    ];

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

        // Ambil tanda tangan yang sudah tersimpan untuk user ini
        $savedSignature = $this->getSavedSignature($user->id, $tanggal);

        // Ambil semua tanda tangan penandatangan untuk dokumen ini
        $docSignatures = $this->getAllDocSignatures($tanggal);

        return Inertia::render('Dokumen/Index', [
            'lemburs'        => $lemburs,
            'lrlkItems'      => $lrlkItems,
            'tanggal'        => $tanggal,
            'totalJamDibayar' => $totalJamDibayar,
            'userRole'       => $user->role,
            'userName'       => $user->name,
            'userJabatan'    => self::JABATAN_MAP[$user->role] ?? $user->role,
            'savedSignature' => $savedSignature,
            'docSignatures'  => $docSignatures,
        ]);
    }

    /**
     * Simpan tanda tangan digital penandatangan
     */
    public function simpanTandaTangan(Request $request)
    {
        $this->checkAccess();

        $user = auth()->user();

        $request->validate([
            'tanda_tangan'   => 'required|string|max:2000000',
            'tanggal'        => 'required|date',
            'simpan_permanen' => 'nullable|boolean',
        ]);

        $tanggal = $request->tanggal;
        $ttdData = $request->tanda_tangan;

        // Simpan/update TTD untuk tanggal ini
        DocumentSignature::updateOrCreate(
            [
                'user_id'       => $user->id,
                'tanggal'       => $tanggal,
                'jenis_dokumen' => null,
            ],
            [
                'tanda_tangan' => $ttdData,
            ]
        );

        // Jika simpan permanen, juga simpan tanpa tanggal (sebagai default)
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
        }

        return redirect()->back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    /**
     * Ambil tanda tangan tersimpan user untuk tanggal tertentu
     */
    private function getSavedSignature(int $userId, string $tanggal): ?string
    {
        // Coba cari TTD untuk tanggal ini dulu
        $sig = DocumentSignature::where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->whereNull('jenis_dokumen')
            ->first();

        if ($sig) return $sig->tanda_tangan;

        // Fallback ke TTD permanen
        $permanen = DocumentSignature::where('user_id', $userId)
            ->whereNull('tanggal')
            ->whereNull('jenis_dokumen')
            ->first();

        return $permanen?->tanda_tangan;
    }

    /**
     * Ambil semua tanda tangan penandatangan untuk dokumen pada tanggal tertentu
     * Digunakan untuk tampilan di Print.vue & PrintLrlk.vue
     */
    private function getAllDocSignatures(string $tanggal): array
    {
        $result = [];

        foreach (self::ALLOWED_ROLES as $role) {
            // Cari user dengan role ini
            $userWithRole = \App\Models\User::where('role', $role)->first();
            if (!$userWithRole) continue;

            // Cari TTD-nya
            $ttd = $this->getSavedSignature($userWithRole->id, $tanggal);

            $result[$role] = [
                'nama'         => $userWithRole->name,
                'jabatan'      => self::JABATAN_MAP[$role] ?? $role,
                'tanda_tangan' => $ttd,
            ];
        }

        return $result;
    }

    /**
     * Halaman print SPL khusus penandatangan (dengan tanda tangan mereka)
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

        $docSignatures = $this->getAllDocSignatures($tanggal);

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

        $docSignatures = $this->getAllDocSignatures($tanggal);

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
