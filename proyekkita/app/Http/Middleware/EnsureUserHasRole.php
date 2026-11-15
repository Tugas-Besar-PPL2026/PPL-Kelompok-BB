<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * [FR-01] Otorisasi berdasarkan peran. Dipakai setelah middleware "auth", contoh: ->middleware(['auth', 'role:umkm']).
 * Pengguna yang perannya tidak termasuk daftar menerima 403 Forbidden. Beberapa peran dapat ditulis: role:umkm,kandidat.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole(...$roles), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
