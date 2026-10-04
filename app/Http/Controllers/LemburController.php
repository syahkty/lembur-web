<?php

namespace App\Http\Controllers;

use App\Models\Lembur;
use App\Models\User;
use App\Models\Stasiun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LemburController extends Controller
{
    /**
     * Dashboard — Tampilkan SPL dengan filter tanggal
     */
    public function index(Request $request)
    {
        $user = auth()->user();
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
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Form buat SPL — hanya Mandor & Admin
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
            ->get(['id', 'name', 'no_employe', 'stasiun_id', 'shift', 'mandor_id']);

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

            // Karyawan manual / pengganti sementara: simpan dengan user_id = mandor
            // dan catat nama aslinya di keperluan
            $isManual = !empty($item['is_manual']);

            if ($isManual) {
                // Simpan sebagai record mandor dengan catatan nama pengganti
                $namaManual     = $item['nama_manual'] ?? 'Pengganti Sementara';
                $noEmpManual    = $item['no_employe_manual'] ?? '-';
                $keperluan      = "[Pengganti: {$namaManual} / No.{$noEmpManual}]" . (!empty($item['keperluan']) ? ' ' . $item['keperluan'] : '');

                Lembur::create([
                    'user_id'     => $mandorId,   // Dikaitkan ke mandor sementara
                    'mandor_id'   => $mandorId,
                    'tanggal'     => $request->tanggal,
                    'jam_mulai'   => $isLembur ? ($item['jam_mulai'] ?? null) : null,
                    'jam_selesai' => $isLembur ? ($item['jam_selesai'] ?? null) : null,
                    'keterangan'  => $item['keterangan'],
                    'keperluan'   => $keperluan,
                    'kategori'    => $kategori,
                    'status'      => $status,
                ]);
            } else {
                if (empty($item['user_id'])) continue;

                Lembur::updateOrCreate(
                    [
                        'user_id' => $item['user_id'],
                        'tanggal' => $request->tanggal,
                    ],
                    [
                        'mandor_id'   => $mandorId,
                        'jam_mulai'   => $isLembur ? ($item['jam_mulai'] ?? null) : null,
                        'jam_selesai' => $isLembur ? ($item['jam_selesai'] ?? null) : null,
                        'keterangan'  => $item['keterangan'],
                        'keperluan'   => $item['keperluan'] ?? null,
                        'kategori'    => $kategori,
                        'status'      => $status,
                    ]
                );
            }
        }

        return redirect()->route('dashboard', ['tanggal' => $request->tanggal]);
    }

    /**
     * Form edit/revisi SPL — untuk Mandor
     */
    public function edit(Lembur $lembur)
    {
        $user = auth()->user();

        // Hanya mandor pembuat atau admin yang bisa edit
        if ($user->role === 'mandor' && $lembur->mandor_id !== $user->id) {
            abort(403, 'Akses ditolak.');
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

        if ($user->role === 'mandor' && $lembur->mandor_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'tanggal' => 'nullable|date',
            'kategori' => 'nullable|string',
            'jam_mulai' => 'nullable',
            'jam_selesai' => 'nullable',
            'keterangan' => 'required|string',
            'keperluan' => 'nullable|string',
        ]);

        $kategori = $request->kategori ?? $lembur->kategori ?? 'lembur';
        $isLembur = ($kategori === 'lembur');
        $status = $isLembur ? 'pending' : $kategori;

        $lembur->update([
            'tanggal' => $request->tanggal ?? $lembur->tanggal,
            'jam_mulai' => $isLembur ? ($request->jam_mulai ?? null) : null,
            'jam_selesai' => $isLembur ? ($request->jam_selesai ?? null) : null,
            'keterangan' => $request->keterangan,
            'keperluan' => $request->keperluan,
            'kategori' => $kategori,
            'status' => $status,
            'alasan_revisi' => null,
        ]);

        return redirect()->route('dashboard', ['tanggal' => $lembur->tanggal]);
    }

    /**
     * Hapus lembur / batalkan baris SPL
     */
    public function destroy(Lembur $lembur)
    {
        $user = auth()->user();

        if ($user->role === 'mandor' && $lembur->mandor_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role === 'karyawan') {
            abort(403, 'Akses ditolak.');
        }

        $lembur->delete();

        return redirect()->back();
    }

    /**
     * Karyawan ACC lembur
     */
    public function acc(Lembur $lembur)
    {
        if (auth()->id() !== $lembur->user_id) {
            abort(403, 'Akses ditolak.');
        }

        $lembur->update(['status' => 'disetujui']);

        return redirect()->back();
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

        return redirect()->back();
    }

    /**
     * Riwayat lembur karyawan + total jam
     */
    public function riwayat(Request $request)
    {
        $user = auth()->user();
        $bulan = $request->get('bulan', Carbon::now()->format('Y-m'));

        $lemburs = Lembur::where('user_id', $user->id)
            ->where('tanggal', 'like', $bulan . '%')
            ->where('status', 'disetujui')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Hitung total jam per entry & grand total (positif & mendukung lewat tengah malam)
        $totalMenit = 0;
        $lemburData = $lemburs->map(function ($l) use (&$totalMenit) {
            if (!$l->jam_mulai || !$l->jam_selesai) {
                return array_merge($l->toArray(), [
                    'durasi_jam' => 0,
                ]);
            }

            [$h1, $m1] = array_map('intval', explode(':', $l->jam_mulai));
            [$h2, $m2] = array_map('intval', explode(':', $l->jam_selesai));
            $diff = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
            if ($diff < 0) {
                $diff += 24 * 60; // Menangani lembur lewat tengah malam
            }

            $totalMenit += $diff;

            return array_merge($l->toArray(), [
                'durasi_jam' => round($diff / 60, 1),
            ]);
        });

        return Inertia::render('Lembur/Riwayat', [
            'lemburs' => $lemburData,
            'bulan' => $bulan,
            'totalJam' => round($totalMenit / 60, 1),
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
            'lemburs' => $query->orderBy('id')->get(),
            'tanggal' => $tanggal,
            'userRole' => $user->role,
        ]);
    }
}