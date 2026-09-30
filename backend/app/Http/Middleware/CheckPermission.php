<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($request->user()?->hasPermission($permission)) {
            return $next($request);
        }

        $message = 'Anda tidak memiliki akses untuk melakukan aksi ini. Hubungi Owner/Admin jika Anda merasa ini seharusnya diizinkan.';

        // Request AJAX/API: tetap balas JSON 403 seperti biasa, biar fetch()/axios
        // di frontend bisa nangkep errornya secara terprogram kalau perlu.
        //
        // Syarat `is('api/*')` ditambahkan karena frontend Vue memanggil API
        // dengan token di header, bukan cookie session. Kalau suatu saat axios
        // mengirim Accept header yang tidak menyertakan JSON, isPrefix()
        // akan mengembalikan HTML redirect -- dan SPA akantrying mem-parsing
        // HTML itu sebagai JSON, menghasilkan SyntaxError yang sangat membingungkan.
        // Setiap route di bawah /api WAJIB membalas JSON apa pun hasilnya.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        // Request biasa (klik link/submit form): redirect dengan flash 'error',
        // yang otomatis ditangkap oleh sistem toast notifikasi global aplikasi
        // (lihat showGlobalToast() di layouts/app.blade.php) sehingga user
        // melihat notifikasi merah yang jelas, bukan halaman error 403 polos.
        //
        // Untuk GET (membuka halaman langsung tanpa akses), redirect ke Dashboard
        // -- karena Dashboard selalu bisa diakses semua role -- supaya tidak
        // ter-redirect balik ke halaman yang sama dan bikin user bingung.
        // Untuk POST/PUT/PATCH/DELETE (submit form aksi), redirect kembali ke
        // halaman asal agar konteks/isian form tidak hilang.
        if ($request->isMethod('get')) {
            return redirect()->route('admin.dashboard')->with('error', $message);
        }

        return redirect()->back()->with('error', $message);
    }
}