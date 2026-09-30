<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['permission' => CheckPermission::class]);

        /*
         * Backend berjalan di belakang nginx, jadi X-Forwarded-* yang
         * dikirim nginx adalah satu-satunya sumber informasi IP asli.
         * Tanpa trustProxies, request()->ip() selalu mengembalikan
         * 127.0.0.1, yang membuat rate limiter menganggap SEMUA user
         * berasal dari satu IP dan bisa memblokir akses bersama.
         *
         * Default: percaya semua proxy (pilihan paling aman untuk
         * arsitektur 3-server di jaringan internal/LAN). Kalau backend
         * diexpose ke internet, batasi ke IP proxy terdaftar:
         *     TRUSTED_PROXIES=10.0.0.1,10.0.0.2
         *
         * Catatan: Illuminate\Http\Middleware\HandleCors sudah otomatis
         * ada di global stack Laravel 11, memakai config/cors.php.
         */
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES')
                ? explode(',', (string) env('TRUSTED_PROXIES'))
                : '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        /*
         * Group `api` di Laravel 11 secara default KOSONG (tidak ada
         * throttle, tidak ada SubstituteBindings). Untuk API publik yang
         * dipanggil SPA dari server lain, kita butuh minimal:
         *   - SubstituteBindings supaya route model binding ($armada->{id}) bekerja
         *   - throttle agar endpoint auth tidak bisa di-brute-force
         */
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->throttleApi('120,1'); // 120 request/menit per user
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Paksa semua error di bawah /api dibalas sebagai JSON -- termasuk
         * 404, 403, 419, dan 500.
         *
         * Tanpa ini, Laravel memakai $request->expectsJson() yang hanya true
         * kalau header Accept menyertakan "application/json". Kalau axios
         * mengirim Accept yang berbeda, user akan menerima halaman HTML error
         * di tengah SPA, dan axios akan gagal dengan "Unexpected token <"
         * -- error yang jauh lebih sulit ditelusuri daripada 403 JSON biasa.
         *
         * Route publik (tanpa token) seperti /api/public/invoices/verify juga
         * sudah otomatis JSON karena controllernya memang mengembalikan JSON.
         */
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
