<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: setelah pemisahan 3 server, hal paling penting yang harus
 * dijaga adalah (a) health check tetap hidup, (b) endpoint publik invoice
 * tetap terbuka tanpa token, dan (c) route /api benar-benar terdaftar.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_endpoint_verifikasi_invoice_terbuka_tanpa_token(): void
    {
        // QR di PDF invoice diarahkan ke sini. Seseorang yang memindai QR
        // di kertas belum tentu punya akun, jadi endpoint ini WAJIB
        // terbuka -- kalau tidak, semua QR invoice yang sudah tercetak
        // jadi tidak berguna.
        $this->getJson('/api/public/invoices/verify/token-yang-tidak-ada')
            ->assertOk()
            ->assertJsonPath('valid', false);
    }

    public function test_endpoint_api_terdaftar_dan_menolak_tanpa_token(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_daftar_permission_user_dikembalikan_dengan_bentuk_yang_diharapkan(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'nama', 'email', 'role', 'role_name', 'is_active', 'is_owner', 'permissions'],
            ]);
    }
}
