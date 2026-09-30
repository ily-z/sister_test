<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Penegakan permission di lapisan API.
 *
 * Ini pengaman utama SPA: karena frontend hanya menyembunyikan menu,
 * satu-satunya tempat yang benar-benar menolak akses adalah middleware
 * `permission:` di routes/api.php. Kalau ada route API yang lupa
 *middleware, test di sini yang akan menangkapnya.
 *
 * Role `reception` sengaja dipakai sebagai contoh: dia punya semua
 * permission berakhiran ".view" tapi tidak punya create/edit/delete,
 * jadi perbedaan lihat vs ubah bisa diuji dengan jelas.
 */
class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /**
     * Pasang permission "<resource>.view" untuk role reception.
     *
     * Di seeders, reception sudah mendapat semua permission .view.
     * Test iniMEMBUATNYA secara eksplisit supaya tidak bergantung pada
     * isi DatabaseSeeder -- kalau suatu saat isi seeder berubah, test ini
     * tetap menguji aturan yang sama.
     */
    protected function grantViewToReception(): void
    {
        $permissionIds = Permission::query()
            ->whereIn('nama_permission', [
                'users.view', 'bookings.view', 'fleet.view', 'finance.view',
                'service.view', 'analytics.view',
            ])
            ->pluck('id', 'nama_permission');

        foreach ($permissionIds as $id) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => 'reception',
                'permission_id' => $id,
            ]);
        }
    }

    public function test_reception_can_view_but_cannot_mutate_business_modules(): void
    {
        $this->grantViewToReception();
        $reception = User::factory()->create(['role' => 'reception']);
        $token = $this->tokenFor($reception);

        // Boleh melihat...
        $this->withToken($token)->getJson('/api/bookings')->assertOk();
        $this->withToken($token)->getJson('/api/keuangan')->assertOk();
        $this->withToken($token)->getJson('/api/armada')->assertOk();
        $this->withToken($token)->getJson('/api/users')->assertOk();

        // ... tapi tidak boleh membuat / mengubah.
        $this->withToken($token)->postJson('/api/bookings', [])->assertForbidden();
        $this->withToken($token)->getJson('/api/bookings/create')->assertForbidden();
        $this->withToken($token)->getJson('/api/armada/create')->assertForbidden();
        $this->withToken($token)->getJson('/api/keuangan/pengeluaran-kantor/create')->assertForbidden();
        $this->withToken($token)->postJson('/api/armada', [])->assertForbidden();
        $this->withToken($token)->postJson('/api/keuangan/pengeluaran-kantor', [])->assertForbidden();
    }

    public function test_reception_cannot_manage_roles_or_permissions(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $token = $this->tokenFor($reception);

        $this->withToken($token)->getJson('/api/permissions')->assertForbidden();
        $this->withToken($token)->getJson('/api/permissions/data?role=reception')->assertForbidden();
        $this->withToken($token)->putJson('/api/permissions', [
            'role' => 'reception',
            'permissions' => [],
        ])->assertForbidden();
        $this->withToken($token)->postJson('/api/permissions/reorder', [
            'roles' => [['slug' => 'reception', 'parent_slug' => 'owner']],
        ])->assertForbidden();
    }

    public function test_reception_tidak_bisa_membuat_user(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);

        $this->withToken($this->tokenFor($reception))
            ->postJson('/api/users', [
                'nama' => 'Calon User',
                'email' => 'calon@example.test',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'reception',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'calon@example.test']);
    }

    public function test_admin_dapat_mengatur_permission_role_bawahan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->withToken($this->tokenFor($admin))
            ->getJson('/api/permissions?role=reception')
            ->assertOk();

        $response->assertJsonStructure([
            'roles', 'permissions', 'permissionGroups', 'selectedRole', 'assigned', 'assignable',
        ]);

        $response->assertJsonPath('selectedRole.slug', 'reception');

        // Admin boleh memberikan permission yang dia miliki sendiri,
        // sehingga daftar "assignable" harus terisi.
        $this->assertNotEmpty($response->json('assignable'));
    }

    public function test_semua_endpoint_api_wajib_menjawab_json(): void
    {
        /*
         * Guardrails untuk arsitektur 3-server: kalau ada route /api yang
         * membalas HTML (mis. karena lupa dibungkus shouldRenderJsonWhen),
         * SPA akan gagal dengan "Unexpected token <" yang sangat sulit
         * ditelusuri. Test ini memanggil endpoint yang MELOLOS otorisasi
         * (dashboard tidak butuh permission) dengan token valid, lalu
         * memastikan body-nya JSON.
         */
        $user = User::factory()->create(['role' => 'owner']);

        $response = $this->withToken($this->tokenFor($user))->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertStringStartsWith(
            '{',
            $response->getContent(),
            'Endpoint /api harus selalu membalas JSON, tidak boleh HTML.'
        );
    }

    public function test_403_dari_api_ikut_dalam_bentuk_json(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);

        $this->withToken($this->tokenFor($reception))
            ->getJson('/api/permissions')
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_422_dari_api_ikut_dalam_bentuk_json(): void
    {
        /*
         * PENTING: token di test ini HANYA boleh milik SATU user.
         * Illuminate\Auth\RequestGuard menyimpan user yang sudah di-resolve
         * selama satu test berjalan, jadi kalau test ini mengirim token
         * user A lalu token user B, request kedua diam-diam masih dijawab
         * dengan user A dan assertion-nya menyesatkan.
         *
         * Endpoint di bawah butuh bookings.create, jadi harus admin --
         * kalau memakai reception, middleware permission mengembalikan 403
         * duluan dan validasi tidak pernah dijalankan.
         */
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/bookings', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
