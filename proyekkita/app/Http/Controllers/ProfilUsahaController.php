<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfilUsahaController extends Controller
{
    public function edit()
    {
        return view('profil-usaha');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nama'      => 'required|string|max:100',
            'bidang'    => 'required|string',
            'deskripsi' => 'required|string',
            'kontak'    => 'required|string|max:20',
            'kota'      => 'required|string|max:50',
            'alamat'    => 'required|string',
            'nib'       => 'nullable|digits:13',
            'tautan'    => 'nullable|string|max:255',
        ]);

        // TODO: simpan $data ke database di commit berikutnya

        return back()->with('success', 'Profil usaha berhasil disimpan.');
    }
}