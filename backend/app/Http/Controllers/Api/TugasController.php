<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\Request;

class TugasController extends Controller
{
    public function index()
    {
        return response()->json(Tugas::latest()->get());
    }

    public function show(Tugas $tugas)
    {
        return response()->json($tugas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'selesai' => 'boolean',
        ]);

        return response()->json(Tugas::create($data), 201);
    }

    public function update(Request $request, Tugas $tugas)
    {
        $data = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'selesai' => 'boolean',
        ]);

        $tugas->update($data);

        return response()->json($tugas);
    }

    public function destroy(Tugas $tugas)
    {
        $tugas->delete();

        return response()->noContent();
    }
}
