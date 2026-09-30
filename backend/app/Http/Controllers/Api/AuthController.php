<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autentikasi untuk SPA di server terpisah.
 *
 * MEMAKAI SANCTUM BEARER TOKEN, BUKAN SESSION/COOKIE.
 * Alasannya (= syarat "bisa diakses dari IP address apapun"):
 *   - Cookie lintas origin butuh SameSite=None + Secure, dan Secure
 *     hanya jalan di HTTPS. Tanpa itu, cookie tidak akan dikirim.
 *   - Sanctum juga menolak wildcard origin kalau supports_credentials
 *     = true.
 *   - Dengan bearer token, frontend cukup menyimpan string di
 *     localStorage dan mengirimkannya sebagai header Authorization.
 *     Tidak butuh HTTPS, tidak butuh domain yang sama, tidak butuh
 *     SameSite. CORS jadi satu-satunya hurdle, dan itu sudah di-set
 *     longgar di config/cors.php.
 *
 * Token di-hash SHA-256 sebelum disimpan di tabel
 * personal_access_tokens, jadi bocornya isi tabel tidak langsung
 * memberi akses.
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], [
            'nama' => 'nama',
            'password' => 'password',
        ]);

        /*
         * Sengaja TIDAK memakai Auth::attempt().
         *
         * Auth::attempt() meng-*authenticate* user pada guard `web`, yang
         * berarti backend ikut mengirim session cookie. Akibatnya Sanctum
         * akan melihat user yang "sudah login via session" dan
         * mengembalikan TransientToken -- yang membuat bearer token milik
         * perangkat lain ikut dianggap sah. Untuk SPA yang menyimpan
         * token di localStorage, itu berarti satu perangkat bisa memakai
         * sesi devices lain tanpa jejak.
         *
         * Verifikasi manual di bawah menjaga API benar-benar stateless:
         * satu-satunya penentu akses adalah header Authorization.
         */
        $user = User::where('nama', $validated['nama'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            // Pesan sengaja disamakan agar tidak membocorkan apakah
            // username ada tapi nonaktif, atau memang tidak ada.
            throw ValidationException::withMessages([
                'nama' => 'Nama atau password salah, atau akun belum aktif.',
            ]);
        }

        // Bersihkan token lama milik user ini supaya tidak menumpuk
        // (mis. setelah login ulang di browser yang sama).
        $namaToken = $this->deviceName($request);
        $user->tokens()->where('name', $namaToken)->delete();

        $token = $user->createToken(
            $namaToken,
            ['*'], // abilities
            now()->addMinutes((int) config('auth.api_token_ttl', 480)), // default 8 jam
        );

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     *
     * Hanya mencabut token yang sedang dipakai, bukan semua token user,
     * supaya user tidak ikut logout dari perangkat lain.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->currentToken($request)?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * POST /api/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout dari semua perangkat berhasil.']);
    }

    /**
     * GET /api/auth/me
     *
     * Dipanggil frontend setelah reload halaman, untuk membuktikan token
     * masih valid sekaligus mengambil daftar permission (dipakai untuk
     * menyembunyikan menu / guarding route di sisi client).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /**
     * POST /api/auth/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'current_password' => 'password saat ini',
            'password' => 'password baru',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini salah.',
            ]);
        }

        $user->forceFill(['password' => $validated['password']])->save();

        /*
         * Password berubah => cabut token lain supaya kalau password
         * bocor, sesi yang sudah aktif ikut mati. Token yang sedang
         * dipakai TIDAK dicabut: kalau ikut dicabut, user langsung
         * terlogout di perangkat yang sedang ia pakai dan akan kehilangan
         * form yang belum tersimpan.
         */
        $tokenSekarang = $this->currentToken($request);

        if ($tokenSekarang) {
            $user->tokens()->where('id', '!=', $tokenSekarang->id)->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    /**
     * Token yang sedang dipakai request ini, atau null bila request
     * terautentikasi lewat cara lain (mis. Sanctum::actingAs() di test).
     *
     * Sengaja dibungkus cek is_a(): TransientToken tidak punya properti
     * `id`, jadi memanggil ->id di atas akan fatal error.
     */
    private function currentToken(Request $request): ?PersonalAccessToken
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }

    /**
     * Bentuk payload user yang dipakai SPA.
     */
    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
            'role' => $user->getRawOriginal('role'),
            'role_name' => $user->roleModel()?->nama ?? $user->getRawOriginal('role'),
            'is_active' => (bool) $user->is_active,
            'is_owner' => $user->isOwner(),
            'permissions' => $user->permissionSlugs(),
        ];
    }

    /**
     * Nama token untuk membedakan token per perangkat/browser.
     */
    private function deviceName(Request $request): string
    {
        $agent = (string) $request->userAgent();
        $short = substr($agent, 0, 60);

        return trim((string) $request->input('device_name', $short)) ?: 'spa';
    }
}
