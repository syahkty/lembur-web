<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stasiun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class KaryawanController extends Controller
{
    protected function checkAdmin(): void
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Administrator.');
        }
    }

    public function index()
    {
        $this->checkAdmin();

        $karyawan = User::with(['stasiun', 'mandorUser'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $stasions = Stasiun::orderBy('id')->get();
        $mandors = User::where('role', 'mandor')->orderBy('name')->get(['id', 'name', 'shift']);

        return Inertia::render('Admin/Karyawan', [
            'karyawanList' => $karyawan,
            'stasions' => $stasions,
            'mandors' => $mandors,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
            'no_employe' => 'nullable|string|max:50',
            'role' => 'required|in:admin,mandor,karyawan',
            'stasiun_id' => 'nullable|exists:stasions,id',
            'shift' => 'nullable|in:1,2',
            'mandor_id' => 'nullable|exists:users,id',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'status_pernikahan' => 'nullable|string|max:10',
            'jatah_beras_kg' => 'nullable|numeric|min:0',
            'harga_beras_per_kg' => 'nullable|numeric|min:0',
            'tarif_lembur_per_jam' => 'nullable|numeric|min:0',
        ]);

        $mandorId = $request->mandor_id;
        if (!$mandorId && $request->shift) {
            $matchedMandor = User::where('role', 'mandor')->where('shift', $request->shift)->first();
            if ($matchedMandor) {
                $mandorId = $matchedMandor->id;
            }
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_employe' => $request->no_employe,
            'role' => $request->role,
            'stasiun_id' => $request->stasiun_id,
            'shift' => $request->shift,
            'mandor_id' => $mandorId,
            'gaji_pokok' => $request->gaji_pokok ?: null,
            'status_pernikahan' => $request->status_pernikahan ?: 'TK',
            'jatah_beras_kg' => $request->jatah_beras_kg !== null && $request->jatah_beras_kg !== '' ? $request->jatah_beras_kg : 15.00,
            'harga_beras_per_kg' => $request->harga_beras_per_kg !== null && $request->harga_beras_per_kg !== '' ? $request->harga_beras_per_kg : 14000.00,
            'tarif_lembur_per_jam' => $request->tarif_lembur_per_jam ?: null,
        ]);

        return redirect()->back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdmin();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'no_employe' => 'nullable|string|max:50',
            'role' => 'sometimes|required|in:admin,mandor,karyawan',
            'stasiun_id' => 'nullable|exists:stasions,id',
            'shift' => 'nullable|in:1,2',
            'mandor_id' => 'nullable|exists:users,id',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'status_pernikahan' => 'nullable|string|max:10',
            'jatah_beras_kg' => 'nullable|numeric|min:0',
            'harga_beras_per_kg' => 'nullable|numeric|min:0',
            'tarif_lembur_per_jam' => 'nullable|numeric|min:0',
        ]);

        $data = $request->only(
            'name', 'email', 'no_employe', 'role', 'stasiun_id', 'shift', 'mandor_id',
            'gaji_pokok', 'status_pernikahan', 'jatah_beras_kg', 'harga_beras_per_kg', 'tarif_lembur_per_jam'
        );

        // Jika shift diubah tanpa menyebut mandor_id, otomatis pasangkan ke mandor shift tersebut
        if ($request->has('shift') && !$request->filled('mandor_id') && $request->shift) {
            $matchedMandor = User::where('role', 'mandor')->where('shift', $request->shift)->first();
            if ($matchedMandor) {
                $data['mandor_id'] = $matchedMandor->id;
            }
        }

        // Update password hanya jika diisi
        if ($request->filled('password')) {
            $request->validate(['password' => Rules\Password::defaults()]);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->checkAdmin();

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Administrator Anda sendiri.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
