<?php

namespace App\Http\Controllers;

use App\Models\Stasiun;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StasiunController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Stasiun', [
            'stasions' => Stasiun::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:stasions,kode',
        ]);

        Stasiun::create($request->only('nama', 'kode'));

        return redirect()->back();
    }

    public function update(Request $request, Stasiun $stasiun)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:10|unique:stasions,kode,' . $stasiun->id,
        ]);

        $stasiun->update($request->only('nama', 'kode'));

        return redirect()->back();
    }

    public function destroy(Stasiun $stasiun)
    {
        $stasiun->delete();

        return redirect()->back();
    }
}
