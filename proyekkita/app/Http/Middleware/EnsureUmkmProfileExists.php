<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman proyek UMKM mensyaratkan profil usaha sudah diisi. Dipakai setelah "auth" dan "role:umkm".
 *
 * Belum punya profil bukan pelanggaran akses, jadi bukan 403: pengguna diberi peringatan lalu diarahkan ke halaman
 * pengisian profil (umkm.profil.edit). Profil hanya bisa tersimpan lewat UmkmProfileRequest yang mewajibkan semua
 * isian, sehingga adanya baris profil berarti profil sudah lengkap.
 *
 * Untuk permintaan GET, tujuan awal diingat di session supaya setelah profil tersimpan pengguna dikembalikan ke sana.
 */
class EnsureUmkmProfileExists
{
    /** Kunci session untuk tujuan awal. Hanya berisi path internal, tidak pernah dari input pengguna. */
    public const RETURN_KEY = 'umkm_profile_return';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->umkmProfile) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put(self::RETURN_KEY, $request->getRequestUri());
        }

        return redirect()->route('umkm.profil.edit')->with(
            'warning',
            'Profil usaha kamu belum lengkap. Silakan lengkapi profil usaha terlebih dahulu agar dapat mengakses daftar proyek.'
        );
    }
}
