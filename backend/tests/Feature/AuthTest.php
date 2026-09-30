<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autentikasi API untuk SPA.
 *
 * Semua test memakai endpoint JSON /api/auth/* dan token Sanctum, BUKAN
 * session. Alasannya sama seperti di produksi: frontend berada di server
 * terpisah, jadi tidak ada cookie yang bisa diandalkan.
 *
 * Test ini sengaja menguji hal yang TIDAK terlihat dari sisi client:
 *   - token benar-benar ter-revoke saat logout
 *   - token lama ikut mati ketika password diganti
 *   - akun nonaktif tidak bisa mendapat token baru
 *
 * CATATAN TEKNIS SOAL KENAPA SEBAGIAN ASERSI MEMAKAI assertDatabase*
 * dan bukan request HTTP kedua:
 * Illuminate\Auth\RequestGuard menyimpan user yang sudah di-resolve di
 * properti internal, dan object guard-nya hidup selama satu test. Jadi
 * request kedua di test yang sama bisa saja dijawab dengan user dari
 * request pertama -- apa pun token yang dikirim. Untuk menguji
 * "token dicabut", assertion ke database lebih jujur dan deterministik.
 * Perilaku 401 untuk token yang sudah mati diuji terpisah lewat test
 * yang hanya mengirim SATU request.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Admin,
        ], $overrides));
    }

    // ── Login ──────────────────────────────────────────────────────────

    public function test_user_aktif_bisa_login_dan_mendapat_token(): void
    {
        $this->makeUser([
            'nama' => 'Budi Santoso',
            'password' => 'rahasia123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'nama' => 'Budi Santoso',
            'password' => 'rahasia123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'expires_at',
                'user' => ['id', 'nama', 'email', 'role', 'role_name', 'permissions'],
            ]);

        // Token harus berupa string "id|plaintext", dan bagian plaintext-nya
        // harus bisa dipakai sebagai bearer token.
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $this->makeUser([
            'nama' => 'Budi Santoso',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/auth/login', [
            'nama' => 'Budi Santoso',
            'password' => 'password-salah',
        ])->assertStatus(422)->assertJsonValidationErrors('nama');

        // Tidak boleh ada token yang tersimpan untuk percobaan gagal.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_tidak_aktif_tidak_bisa_login(): void
    {
        $this->makeUser([
            'nama' => 'Siti Nonaktif',
            'password' => 'rahasia123',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'nama' => 'Siti Nonaktif',
            'password' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors('nama');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_gagal_jika_nama_tidak_ada(): void
    {
        $this->postJson('/api/auth/login', [
            'nama' => 'Tidak Terdaftar',
            'password' => 'apapun',
        ])->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_validasi_nama_dan_password_wajib_diisi(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nama', 'password']);
    }

    public function test_login_dibatasi_rate_limit(): void
    {
        $this->makeUser(['nama' => 'Budi', 'password' => 'rahasia123']);

        // Endpoint login dibatasi throttle:10,1. Kirim 12x dalam sekejap:
        // 10 pertama lolos ke validasi (gagal login), sisanya kena 429.
        $lastResponse = null;

        for ($i = 0; $i < 12; $i++) {
            $lastResponse = $this->postJson('/api/auth/login', [
                'nama' => 'Budi',
                'password' => 'salah-'.$i,
            ]);
        }

        $this->assertSame(429, $lastResponse->status());
    }

    public function test_login_tidak_perlu_https_maupun_cookie(): void
    {
        // Keberadaan API ini adalah syarat "frontend bisa diakses dari IP
        // address seadanya": tidak ada session cookie, tidak ada CSRF token.
        $this->makeUser(['nama' => 'Budi', 'password' => 'rahasia123']);

        $response = $this->postJson('/api/auth/login', [
            'nama' => 'Budi',
            'password' => 'rahasia123',
        ])->assertOk();

        $response->assertCookieMissing(config('session.cookie'));
        $this->assertFalse(session()->isStarted());
    }

    // ── Endpoint terlindungi ───────────────────────────────────────────

    public function test_guest_tidak_bisa_akses_endpoint_terlindungi(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_token_yang_sudah_dicabut_ditolak(): void
    {
        // Hanya SATU request di test ini, jadi RequestGuard tidak punya
        // user yang ter-cache dari request sebelumnya.
        $user = $this->makeUser();
        $token = $user->createToken('ponsel');
        $token->accessToken->delete();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_token_kadaluarsa_ditolak(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('ponsel', ['*'], now()->subMinute());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_token_asing_ditolak(): void
    {
        $this->withHeader('Authorization', 'Bearer 1|pasta-ngawur')
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    // ── Me ─────────────────────────────────────────────────────────────

    public function test_me_mengembalikan_daftar_permission(): void
    {
        $user = $this->makeUser();

        $token = $user->createToken('ponsel')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.nama', $user->nama)
            ->assertJsonStructure(['user' => ['permissions']]);
    }

    // ── Logout ─────────────────────────────────────────────────────────

    public function test_logout_mencabut_token_yang_dipakai(): void
    {
        $user = $this->makeUser(['nama' => 'Budi', 'password' => 'rahasia123']);

        $token = $this->postJson('/api/auth/login', [
            'nama' => 'Budi',
            'password' => 'rahasia123',
        ])->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_hanya_mencabut_token_perangkat_ini(): void
    {
        $user = $this->makeUser();

        $ponsel = $user->createToken('ponsel');
        $laptop = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$ponsel->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // Token laptop harus tetap berlaku -- logout di satu perangkat
        // tidak boleh mematikan akses operator di perangkat lain.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ponsel->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $laptop->accessToken->id]);
    }

    public function test_logout_all_mencabut_semua_token(): void
    {
        $user = $this->makeUser();

        $ponsel = $user->createToken('ponsel');
        $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$ponsel->plainTextToken)
            ->postJson('/api/auth/logout-all')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── Ganti password ─────────────────────────────────────────────────

    public function test_ganti_password_mencabut_token_lain(): void
    {
        $user = $this->makeUser(['nama' => 'Budi', 'password' => 'rahasia123']);

        $ponsel = $user->createToken('ponsel');
        $laptop = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$ponsel->plainTextToken)
            ->postJson('/api/auth/password', [
                'current_password' => 'rahasia123',
                'password' => 'rahasia456',
                'password_confirmation' => 'rahasia456',
            ])->assertOk();

        // Sesi di perangkat lain harus ikut mati: kalau password bocor,
        // akses yang sudah aktif tidak boleh tetap jalan.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $laptop->accessToken->id]);

        // Tapi token yang sedang dipakai TIDAK ikut dicabut, supaya user
        // tidak terlogout dari perangkat yang sedang ia pakai.
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ponsel->accessToken->id]);

        // Password baru harus bisa dipakai untuk login.
        $this->postJson('/api/auth/login', [
            'nama' => 'Budi',
            'password' => 'rahasia456',
        ])->assertOk();

        // Password lama harus ditolak.
        $this->postJson('/api/auth/login', [
            'nama' => 'Budi',
            'password' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_ganti_password_gagal_jika_password_saat_ini_salah(): void
    {
        $user = $this->makeUser(['password' => 'rahasia123']);
        $token = $user->createToken('ponsel')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/password', [
                'current_password' => 'salah',
                'password' => 'rahasia456',
                'password_confirmation' => 'rahasia456',
            ])->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_baru_wajib_minimal_8_karakter(): void
    {
        $user = $this->makeUser(['password' => 'rahasia123']);
        $token = $user->createToken('ponsel')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/password', [
                'current_password' => 'rahasia123',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_ganti_password_tidak_perlu_login_ulang(): void
    {
        $user = $this->makeUser(['nama' => 'Budi', 'password' => 'rahasia123']);

        $ponsel = $user->createToken('ponsel');

        $this->withHeader('Authorization', 'Bearer '.$ponsel->plainTextToken)
            ->postJson('/api/auth/password', [
                'current_password' => 'rahasia123',
                'password' => 'rahasia456',
                'password_confirmation' => 'rahasia456',
            ])->assertOk()
            // Token yang sedang dipakai sengaja TIDAK dikembalikan:
            // yang tersimpan di database sudah berupa hash, sedangkan
            // frontend sudah menyimpan versi plaintext-nya di localStorage.
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ponsel->accessToken->id]);
    }
}
