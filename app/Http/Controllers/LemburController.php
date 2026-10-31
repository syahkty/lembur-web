<?php

namespace App\Http\Controllers;

use App\Models\Lembur;
use App\Models\User;
use App\Models\Stasiun;
use App\Models\DocumentSignature;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LemburController extends Controller
{
    /**
     * Dashboard — Tampilkan SPL dengan filter tanggal
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Penandatangan dokumen punya halaman tersendiri
        $penandatangan = ['asisten', 'askep', 'ktu', 'administratur', 'hrga'];
        if (in_array($user->role, $penandatangan)) {
            return redirect()->route('dokumen.index', $request->only('tanggal'));
        }

        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());

        $query = Lembur::with(['user.stasiun', 'mandor'])->where('tanggal', $tanggal)->latest();

        // Filter berdasarkan Role
        if ($user->role === 'karyawan') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'mandor') {
            $query->where('mandor_id', $user->id);
        }
        // Admin bisa lihat semua

        return Inertia::render('Dashboard', [
            'lemburs' => $query->get(),
            'userRole' => $user->role,
            'userSavedSignature' => $user->tanda_tangan,
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Form buat SPL — hanya Mandor
     */
    public function create()
    {
        $user = auth()->user();

        // Hanya Mandor yang dapat membuat SPL
        if ($user->role !== 'mandor') {
            abort(403, 'Hanya mandor yang dapat membuat SPL.');
        }

        $stasions = Stasiun::all();

        // Ambil karyawan di bawah tanggung jawab mandor ini
        $karyawanList = User::where('role', 'karyawan')
            ->where('mandor_id', $user->id)
            ->with('stasiun')
            ->orderBy('stasiun_id')
            ->get(['id', 'name', 'no_employe', 'stasiun_id', 'shift', 'mandor_id', 'gaji_pokok', 'tarif_lembur_per_jam']);

        return Inertia::render('Lembur/Create', [
            'karyawanList' => $karyawanList,
            'stasions'     => $stasions,
            'userRole'     => $user->role,
            'userShift'    => $user->shift,
        ]);
    }

    /**
     * Simpan SPL kolektif per shift
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Hanya mandor yang boleh store
        if ($user->role !== 'mandor') {
            abort(403, 'Hanya mandor yang dapat menerbitkan SPL.');
        }

        $mandorId = $user->id;

        $request->validate([
            'tanggal'             => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.kategori'    => 'required|string',
            'items.*.keterangan'  => 'required|string',
        ]);

        foreach ($request->items as $item) {
            if (isset($item['sertakan']) && !$item['sertakan']) {
                continue;
            }

            $kategori = $item['kategori'] ?? 'lembur';
            $isLembur = ($kategori === 'lembur');

            // 'tidak_lembur' disimpan tanpa jam, status = 'tidak_lembur'
            $status = $isLembur ? 'pending' : $kategori;

            $isManual = !empty($item['is_manual']);

            if ($isManual) {
                // Simpan sebagai record mandor dengan catatan nama pengganti
                $namaManual     = $item['nama_manual'] ?? 'Pengganti Sementara';
                $noEmpManual    = $item['no_employe_manual'] ?? '-';
                $keperluan      = "[Pengganti: {$namaManual} / No.{$noEmpManual}]" . (!empty($item['keperluan']) ? ' ' . $item['keperluan'] : '');

                Lembur::create([
                    'user_id'       => $mandorId,
                    'mandor_id'     => $mandorId,
                    'tanggal'       => $request->tanggal,
                    'jam_mulai'     => $isLembur ? ($item['jam_mulai'] ?? null) : null,
                    'jam_selesai'   => $isLembur ? ($item['jam_selesai'] ?? null) : null,
                    'jam_mulai_2'   => $isLembur ? ($item['jam_mulai_2'] ?? null) : null,
                    'jam_selesai_2' => $isLembur ? ($item['jam_selesai_2'] ?? null) : null,
                    'keterangan'    => $item['keterangan'],
                    'keterangan_2'  => $isLembur ? ($item['keterangan_2'] ?? null) : null,
                    'keperluan'     => $keperluan,
                    'kategori'      => $kategori,
                    'status'        => $status,
                ]);
            } else {
                if (empty($item['user_id'])) continue;

                // Proteksi IDOR: Pastikan karyawan yang diikutsertakan adalah bawahan mandor ini
                $isBawahan = User::where('id', $item['user_id'])
                    ->where('mandor_id', $mandorId)
                    ->exists();

                if (!$isBawahan) {
                    continue;
                }

                $existing = Lembur::where('user_id', $item['user_id'])
                    ->where('tanggal', $request->tanggal)
                    ->first();

                // Jangan timpa jika lembur sudah di-ACC oleh karyawan
                if ($existing && $existing->status === 'disetujui') {
                    continue;
                }

                Lembur::updateOrCreate(
                    [
                        'user_id' => $item['user_id'],
                        'tanggal' => $request->tanggal,
                    ],
                    [
                        'mandor_id'     => $mandorId,
                        'jam_mulai'     => $isLembur ? ($item['jam_mulai'] ?? null) : null,
                        'jam_selesai'   => $isLembur ? ($item['jam_selesai'] ?? null) : null,
                        'jam_mulai_2'   => $isLembur ? ($item['jam_mulai_2'] ?? null) : null,
                        'jam_selesai_2' => $isLembur ? ($item['jam_selesai_2'] ?? null) : null,
                        'keterangan'    => $item['keterangan'],
                        'keterangan_2'  => $isLembur ? ($item['keterangan_2'] ?? null) : null,
                        'keperluan'     => $item['keperluan'] ?? null,
                        'kategori'      => $kategori,
                        'status'        => $status,
                    ]
                );
            }
        }

        return redirect()->route('dashboard', ['tanggal' => $request->tanggal])
            ->with('success', 'SPL berhasil disimpan.');
    }

    /**
     * Form edit/revisi SPL — untuk Mandor
     */
    public function edit(Lembur $lembur)
    {
        $user = auth()->user();

        if ($user->role !== 'admin' && ($user->role !== 'mandor' || $lembur->mandor_id !== $user->id)) {
            abort(403, 'Akses ditolak.');
        }

        if ($lembur->status === 'disetujui') {
            return redirect()->route('dashboard', ['tanggal' => $lembur->tanggal])
                ->with('error', 'Lembur ini sudah di-ACC oleh karyawan dan terkunci dari perubahan.');
        }

        $lembur->load(['user.stasiun']);

        return Inertia::render('Lembur/Edit', [
            'lembur' => $lembur,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Update/revisi SPL
     */
    public function update(Request $request, Lembur $lembur)
    {
        $user = auth()->user();

        if ($user->role !== 'admin' && ($user->role !== 'mandor' || $lembur->mandor_id !== $user->id)) {
            abort(403, 'Akses ditolak.');
        }

        if ($lembur->status === 'disetujui') {
            return redirect()->route('dashboard', ['tanggal' => $lembur->tanggal])
                ->with('error', 'Lembur ini sudah disetujui (ACC) oleh karyawan dan tidak dapat diubah oleh mandor. Minta karyawan untuk membatalkan ACC terlebih dahulu jika perlu revisi.');
        }

        $request->validate([
            'tanggal'       => 'nullable|date',
            'kategori'      => 'nullable|string',
            'jam_mulai'     => 'nullable',
            'jam_selesai'   => 'nullable',
            'jam_mulai_2'   => 'nullable',
            'jam_selesai_2' => 'nullable',
            'keterangan'    => 'required|string',
            'keterangan_2'  => 'nullable|string',
            'keperluan'     => 'nullable|string',
        ]);

        $kategori = $request->kategori ?? $lembur->kategori ?? 'lembur';
        $isLembur = ($kategori === 'lembur');
        $status = $isLembur ? 'pending' : $kategori;

        $lembur->update([
            'tanggal'       => $request->tanggal ?? $lembur->tanggal,
            'jam_mulai'     => $isLembur ? ($request->jam_mulai ?? null) : null,
            'jam_selesai'   => $isLembur ? ($request->jam_selesai ?? null) : null,
            'jam_mulai_2'   => $isLembur ? ($request->jam_mulai_2 ?? null) : null,
            'jam_selesai_2' => $isLembur ? ($request->jam_selesai_2 ?? null) : null,
            'keterangan'    => $request->keterangan,
            'keterangan_2'  => $isLembur ? ($request->keterangan_2 ?? null) : null,
            'keperluan'     => $request->keperluan,
            'kategori'      => $kategori,
            'status'        => $status,
            'alasan_revisi' => null,
        ]);

        return redirect()->route('dashboard', ['tanggal' => $lembur->tanggal])
            ->with('success', 'Data lembur berhasil diperbarui.');
    }

    /**
     * Hapus lembur / batalkan baris SPL
     */
    public function destroy(Lembur $lembur)
    {
        $user = auth()->user();

        if ($user->role !== 'admin' && ($user->role !== 'mandor' || $lembur->mandor_id !== $user->id)) {
            abort(403, 'Akses ditolak.');
        }

        if ($lembur->status === 'disetujui') {
            return redirect()->back()
                ->with('error', 'Lembur yang sudah di-ACC oleh karyawan tidak dapat dihapus.');
        }

        $lembur->delete();

        return redirect()->back()->with('success', 'Data lembur berhasil dihapus.');
    }

    /**
     * Karyawan ACC lembur dengan tanda tangan digital & dokumentasi foto
     */
    public function acc(Request $request, Lembur $lembur)
    {
        $user = auth()->user();
        if ($user->id !== $lembur->user_id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'tanda_tangan' => 'required|string|max:2000000',
            'simpan_permanen' => 'nullable|boolean',
            'foto_dokumentasi' => 'nullable',
        ]);

        $fotoPath = null;

        // Tangani file foto jika diupload via multipart form dengan validasi gambar ketat
        if ($request->hasFile('foto_dokumentasi')) {
            $request->validate([
                'foto_dokumentasi' => 'file|image|mimes:jpeg,png,jpg,webp|max:10240',
            ]);
            $path = $request->file('foto_dokumentasi')->store('dokumentasi', 'public');
            $fotoPath = '/storage/' . $path;
        } elseif ($request->filled('foto_dokumentasi') && str_starts_with($request->foto_dokumentasi, 'data:image')) {
            if (preg_match('/^data:image\/(jpeg|png|jpg|webp);base64,/', $request->foto_dokumentasi)) {
                $base64Image = $request->foto_dokumentasi;
                $parts = explode(',', $base64Image);
                if (count($parts) === 2) {
                    $decodedData = base64_decode($parts[1]);
                    if ($decodedData !== false && strlen($decodedData) <= 10 * 1024 * 1024) {
                        $info = @getimagesizefromstring($decodedData);
                        if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                            $ext = ($info[2] === IMAGETYPE_PNG) ? 'png' : (($info[2] === IMAGETYPE_WEBP) ? 'webp' : 'jpg');
                            $fileName = 'dokumentasi_' . $lembur->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            Storage::disk('public')->put('dokumentasi/' . $fileName, $decodedData);
                            $fotoPath = '/storage/dokumentasi/' . $fileName;
                        }
                    }
                }
            }
        }

        // Simpan tanda tangan permanen di profil user jika dicentang
        if ($request->boolean('simpan_permanen') && $request->tanda_tangan) {
            $user->update(['tanda_tangan' => $request->tanda_tangan]);
        }

        // Hitung jam dibayar ekuivalen & upah lembur (PP No. 35/2021)
        $jamDibayar = $lembur->hitungJamDibayar();
        $upahLembur = $lembur->hitungUpahLembur();

        $lembur->update([
            'status' => 'disetujui',
            'tanda_tangan' => $request->tanda_tangan,
            'foto_dokumentasi' => $fotoPath ?: $lembur->foto_dokumentasi,
            'jam_lembur_dibayar' => $jamDibayar,
            'upah_lembur' => $upahLembur,
        ]);

        return redirect()->back()->with('success', 'Lembur berhasil disetujui dengan tanda tangan digital.');
    }

    /**
     * Karyawan membatalkan persetujuan (ACC) lembur
     */
    public function batalAcc(Lembur $lembur)
    {
        $user = auth()->user();
        if ($user->id !== $lembur->user_id) {
            abort(403, 'Hanya karyawan pemilik lembur yang dapat membatalkan persetujuan.');
        }

        if ($lembur->status !== 'disetujui') {
            return redirect()->back()->with('error', 'Lembur ini belum atau tidak dalam status disetujui.');
        }

        $lembur->update([
            'status'             => 'pending',
            'tanda_tangan'       => null,
            'jam_lembur_dibayar' => null,
            'upah_lembur'        => null,
        ]);

        return redirect()->back()->with('success', 'Persetujuan lembur berhasil dibatalkan. Status kembali ke Pending dan dapat direvisi oleh mandor jika diperlukan.');
    }

    /**
     * Upload / perbarui foto dokumentasi lembur (karyawan atau mandor)
     */
    public function uploadFoto(Request $request, Lembur $lembur)
    {
        $user = auth()->user();
        if ($user->id !== $lembur->user_id && $user->role !== 'mandor' && $user->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $request->validate([
                'foto' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:10240',
            ]);
            $path = $request->file('foto')->store('dokumentasi', 'public');
            $fotoPath = '/storage/' . $path;
        } elseif ($request->filled('foto') && str_starts_with($request->foto, 'data:image')) {
            if (preg_match('/^data:image\/(jpeg|png|jpg|webp);base64,/', $request->foto)) {
                $parts = explode(',', $request->foto);
                if (count($parts) === 2) {
                    $decodedData = base64_decode($parts[1]);
                    if ($decodedData !== false && strlen($decodedData) <= 10 * 1024 * 1024) {
                        $info = @getimagesizefromstring($decodedData);
                        if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                            $ext = ($info[2] === IMAGETYPE_PNG) ? 'png' : (($info[2] === IMAGETYPE_WEBP) ? 'webp' : 'jpg');
                            $fileName = 'dokumentasi_' . $lembur->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            Storage::disk('public')->put('dokumentasi/' . $fileName, $decodedData);
                            $fotoPath = '/storage/dokumentasi/' . $fileName;
                        }
                    }
                }
            }
        }

        if ($fotoPath) {
            $lembur->update(['foto_dokumentasi' => $fotoPath]);
            return redirect()->back()->with('success', 'Foto dokumentasi lembur berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Gagal memproses foto dokumentasi. Pastikan format gambar valid (JPG, PNG, WebP) dan ukuran maksimal 10MB.');
    }

    /**
     * Karyawan tolak lembur + alasan revisi
     */
    public function tolak(Request $request, Lembur $lembur)
    {
        if (auth()->id() !== $lembur->user_id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'alasan_revisi' => 'required|string|max:1000',
        ]);

        $lembur->update([
            'status' => 'ditolak',
            'alasan_revisi' => $request->alasan_revisi,
        ]);

        return redirect()->back()->with('success', 'Lembur ditolak, alasan revisi terkirim ke mandor.');
    }

    /**
     * Riwayat lembur karyawan + perhitungan Rupiah & PP 35/2021
     */
    public function riwayat(Request $request)
    {
        $user = auth()->user();
        $bulan = $request->get('bulan', Carbon::now()->format('Y-m'));

        $query = Lembur::with(['user.stasiun', 'mandor'])
            ->where('tanggal', 'like', $bulan . '%')
            ->orderBy('tanggal', 'desc');

        if ($user->role === 'karyawan') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'mandor') {
            $query->where('mandor_id', $user->id);
        }

        $lemburs = $query->get();

        $totalJamNyata = 0;
        $totalJamDibayar = 0;
        $totalUpahLembur = 0;

        $lemburData = $lemburs->map(function ($l) use (&$totalJamNyata, &$totalJamDibayar, &$totalUpahLembur) {
            $durasi = $l->durasi_jam;
            $jamDibayar = $l->jam_dibayar_calc;
            $upah = $l->upah_lembur_calc;

            if ($l->status === 'disetujui') {
                $totalJamNyata += $durasi;
                $totalJamDibayar += $jamDibayar;
                $totalUpahLembur += $upah;
            }

            return array_merge($l->toArray(), [
                'durasi_jam' => $durasi,
                'jam_lembur_dibayar' => $jamDibayar,
                'upah_lembur' => $upah,
                'hari_nama' => $l->hari_nama,
            ]);
        });

        return Inertia::render('Lembur/Riwayat', [
            'lemburs' => $lemburData,
            'bulan' => $bulan,
            'totalJamNyata' => round($totalJamNyata, 1),
            'totalJamDibayar' => round($totalJamDibayar, 1),
            'totalUpahLembur' => round($totalUpahLembur, 0),
            'userProfile' => [
                'id' => $user->id,
                'name' => $user->name,
                'no_employe' => $user->no_employe,
                'gaji_pokok' => (float) ($user->gaji_pokok ?? 0),
                'status_pernikahan' => $user->status_pernikahan ?? 'TK',
                'jatah_beras_kg' => (float) ($user->jatah_beras_kg ?? 15),
                'harga_beras_per_kg' => (float) ($user->harga_beras_per_kg ?? 14000),
                'tunjangan_beras' => (float) ($user->tunjangan_beras ?? 0),
                'total_dasar_upah' => (float) ($user->total_dasar_upah ?? 0),
                'tarif_lembur_per_jam' => (float) ($user->tarif_lembur_per_jam ?? 0),
                'upah_sejam' => (float) ($user->upah_sejam ?? 0),
                'tanda_tangan' => $user->tanda_tangan,
            ],
            'userRole' => $user->role,
        ]);
    }

    /**
     * Print SPL — halaman cetak Surat Perintah Kerja Lembur
     */
    public function print(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $user = auth()->user();

        $query = Lembur::with(['user.stasiun', 'mandor'])->where('tanggal', $tanggal);

        if ($user->role === 'mandor') {
            $query->where('mandor_id', $user->id);
        }

        return Inertia::render('Lembur/Print', [
            'lemburs'       => $query->orderBy('id')->get(),
            'tanggal'       => $tanggal,
            'userRole'      => $user->role,
            'docSignatures' => $this->getDocSignatures($tanggal, 'spl'),
        ]);
    }

    /**
     * Halaman LRLK (Laporan Realisasi Kerja Lembur)
     */
    public function lrlk(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $user = auth()->user();

        $query = Lembur::with(['user.stasiun', 'mandor'])->where('tanggal', $tanggal);

        if ($user->role === 'mandor') {
            $query->where('mandor_id', $user->id);
        }

        $lemburs = $query->orderBy('id')->get();

        $totalJamDibayar = 0;

        $items = $lemburs->map(function ($l, $index) use (&$totalJamDibayar) {
            $isLembur = ($l->kategori === 'lembur' || $l->status === 'disetujui' || $l->status === 'pending');
            $durasiFisik = $isLembur ? $l->durasi_jam : 0;
            $jamTeks = $isLembur ? (fmod($durasiFisik, 1) == 0 ? (string) (int) $durasiFisik : (string) $durasiFisik) : '-';

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
                'id' => $l->id,
                'no' => $index + 1,
                'no_employe' => $l->user->no_employe ?? '-',
                'nama' => $l->user->name ?? '-',
                'dept' => 'Pabrik',
                'tgl' => $tglFormatted,
                'tanggal_full' => $l->tanggal,
                'jenis_pekerjaan' => $pekerjaan,
                'jam_dibayar' => $jamTeks,
                'jam_dibayar_num' => $durasiFisik,
                'jumlah_rp' => '', // Dikosongkan sesuai permintaan
                'jumlah_rp_num' => 0,
                'hasil_kerja' => $l->foto_dokumentasi ? 'Ada Dokumentasi' : '-',
                'foto_dokumentasi' => $l->foto_dokumentasi,
                'tanda_tangan' => $l->tanda_tangan,
                'status' => $l->status,
                'kategori' => $l->kategori,
                'jam_mulai' => $l->jam_mulai,
                'jam_selesai' => $l->jam_selesai,
                'jam_mulai_2' => $l->jam_mulai_2,
                'jam_selesai_2' => $l->jam_selesai_2,
                'durasi_sesi_1' => $l->durasi_sesi_1,
                'durasi_sesi_2' => $l->durasi_sesi_2,
                'has_sesi_2' => $l->has_sesi_2,
            ];
        });

        return Inertia::render('Lembur/Lrlk', [
            'items' => $items,
            'tanggal' => $tanggal,
            'totalJamDibayar' => $totalJamDibayar,
            'totalUpahLembur' => 0,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Cetak LRLK (A4 Landscape/Portrait presisi foto PROS-HCM-02)
     */
    public function printLrlk(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $user = auth()->user();

        $query = Lembur::with(['user.stasiun', 'mandor'])->where('tanggal', $tanggal);

        if ($user->role === 'mandor') {
            $query->where('mandor_id', $user->id);
        }

        $lemburs = $query->orderBy('id')->get();

        $totalJamDibayar = 0;

        $items = $lemburs->map(function ($l, $index) use (&$totalJamDibayar) {
            $isLembur = ($l->kategori === 'lembur' || $l->status === 'disetujui' || $l->status === 'pending');
            $durasiFisik = $isLembur ? $l->durasi_jam : 0;
            $jamTeks = $isLembur ? (fmod($durasiFisik, 1) == 0 ? (string) (int) $durasiFisik : (string) $durasiFisik) : '-';

            if ($isLembur) {
                $totalJamDibayar += $durasiFisik;
            }

            $tglObj = Carbon::parse($l->tanggal);
            $tglFormatted = $tglObj->format('j/n/y');

            $pekerjaan = $l->keterangan ?: ($isLembur ? 'lanjut proses' : ucfirst($l->kategori ?: 'cuti'));
            if ($l->keterangan_2 && $l->keterangan_2 !== $l->keterangan) {
                $pekerjaan .= ' & ' . $l->keterangan_2;
            }

            return [
                'id' => $l->id,
                'no' => $index + 1,
                'no_employe' => $l->user->no_employe ?? '-',
                'nama' => $l->user->name ?? '-',
                'dept' => 'Pabrik',
                'tgl' => $tglFormatted,
                'jenis_pekerjaan' => $pekerjaan,
                'jam_dibayar' => $jamTeks,
                'jumlah_rp' => '', // Dikosongkan sesuai blangko fisik
                'hasil_kerja' => '',
            ];
        });

        return Inertia::render('Lembur/PrintLrlk', [
            'items'          => $items,
            'tanggal'        => $tanggal,
            'totalJamDibayar' => $totalJamDibayar,
            'totalUpahLembur' => 0,
            'userRole'       => $user->role,
            'docSignatures'  => $this->getDocSignatures($tanggal, 'lrlk'),
        ]);
    }

    /**
     * Ambil tanda tangan digital para penandatangan dokumen untuk tanggal & jenis dokumen tertentu ('spl' atau 'lrlk').
     * TIDAK fallback ke tanda tangan permanen agar setiap hari & dokumen wajib disetujui terlebih dahulu.
     */
    private function getDocSignatures(string $tanggal, string $jenisDokumen): array
    {
        $roles = ['asisten', 'askep', 'ktu', 'administratur', 'hrga'];
        $jabatanMap = [
            'asisten'       => 'Asisten',
            'askep'         => 'Asisten Kepala',
            'ktu'           => 'KTU',
            'administratur' => 'Administratur',
            'hrga'          => 'Staff HRGA',
        ];

        $result = [];
        foreach ($roles as $role) {
            $userWithRole = User::where('role', $role)->first();
            if (!$userWithRole) continue;

            // Cari TTD khusus untuk tanggal & jenis dokumen ini saja (tanpa fallback permanen)
            $sig = DocumentSignature::where('user_id', $userWithRole->id)
                ->whereDate('tanggal', $tanggal)
                ->where('jenis_dokumen', $jenisDokumen)
                ->first();

            $result[$role] = [
                'nama'         => $userWithRole->name,
                'jabatan'      => $jabatanMap[$role] ?? $role,
                'tanda_tangan' => $sig?->tanda_tangan,
            ];
        }

        return $result;
    }
}