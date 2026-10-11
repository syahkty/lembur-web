<?php

namespace App\Http\Controllers;

use App\Models\Stasiun;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StasiunController extends Controller
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

        return Inertia::render('Admin/Stasiun', [
            'stasions' => Stasiun::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:stasions,kode',
        ]);

        Stasiun::create($request->only('nama', 'kode'));

        return redirect()->back()->with('success', 'Stasiun berhasil ditambahkan.');
    }

    public function update(Request $request, Stasiun $stasiun)
    {
        $this->checkAdmin();

        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:stasions,kode,' . $stasiun->id,
        ]);

        $stasiun->update($request->only('nama', 'kode'));

        return redirect()->back()->with('success', 'Stasiun berhasil diperbarui.');
    }

    public function destroy(Stasiun $stasiun)
    {
        $this->checkAdmin();

        $stasiun->delete();

        return redirect()->back()->with('success', 'Stasiun berhasil dihapus.');
    }
}
