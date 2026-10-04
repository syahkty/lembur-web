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
    public function index()
    {
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
            'no_employe' => 'nullable|string|max:50',
            'role' => 'required|in:admin,mandor,karyawan',
            'stasiun_id' => 'nullable|exists:stasions,id',
            'shift' => 'nullable|in:1,2',
            'mandor_id' => 'nullable|exists:users,id',
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
        ]);

        return redirect()->back();
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'no_employe' => 'nullable|string|max:50',
            'role' => 'sometimes|required|in:admin,mandor,karyawan',
            'stasiun_id' => 'nullable|exists:stasions,id',
            'shift' => 'nullable|in:1,2',
            'mandor_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->only('name', 'email', 'no_employe', 'role', 'stasiun_id', 'shift', 'mandor_id');

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

        return redirect()->back();
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back();
    }
}
