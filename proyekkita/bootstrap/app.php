<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'umkm.profile' => \App\Http\Middleware\EnsureUmkmProfileExists::class,
        ]);

        // [FR-01] Pengguna yang sudah login dan membuka halaman khusus tamu (landing, masuk, daftar)
        // diarahkan ke halaman awal sesuai perannya.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user() ? route($request->user()->homeRoute()) : '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();