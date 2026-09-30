<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hierarki role & permission lewat endpoint /api/permissions/*.
 *
 * Semua test memakai bearer token, sama seperti frontend SPA. Jalur Blade
 * lama (route admin.permissions.*) tidak lagi diuji karena tidak dipakai
 * lagi setelah pemisahan 3 server.
 */
class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    protected function makeRole(array $attributes = []): Role
    {
        return Role::create(array_merge([
            'slug' => 'operator',
            'nama' => 'Operator',
            'parent_slug' => 'admin',
            'sort_order' => 1,
            'warna' => '#2563eb',
            'is_system' => false,
        ], $attributes));
    }

    public function test_owner_can_edit_custom_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $role = $this->makeRole();

        $this->withToken($this->tokenFor($owner))
            ->patchJson('/api/permissions/roles/'.$role->slug, [
                'nama' => 'Operator Lapangan',
                'parent_slug' => 'admin',
                'warna' => '#e11d48',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Role berhasil diperbarui.')
            ->assertJsonPath('role.slug', 'operator');

        $this->assertDatabaseHas('roles', [
            'slug' => 'operator',
            'nama' => 'Operator Lapangan',
            'warna' => '#e11d48',
        ]);
    }

    public function test_custom_role_can_be_deleted_and_children_are_promoted(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $role = $this->makeRole(['slug' => 'temporary', 'nama' => 'Temporary']);
        $child = $this->makeRole([
            'slug' => 'temporary-child',
            'nama' => 'Temporary Child',
            'parent_slug' => 'temporary',
            'sort_order' => 2,
            'warna' => '#10b981',
        ]);

        $this->withToken($this->tokenFor($owner))
            ->deleteJson('/api/permissions/roles/'.$role->slug)
            ->assertOk()
            ->assertJsonPath('message', 'Role berhasil dihapus.');

        $this->assertDatabaseMissing('roles', ['slug' => 'temporary']);
        $this->assertDatabaseHas('roles', ['slug' => $child->slug, 'parent_slug' => 'admin']);
    }

    public function test_role_yang_masih_punya_user_tidak_bisa_dihapus(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $role = $this->makeRole();
        User::factory()->create(['role' => $role->slug]);

        $this->withToken($this->tokenFor($owner))
            ->deleteJson('/api/permissions/roles/'.$role->slug)
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['slug' => $role->slug]);
    }

    public function test_owner_cannot_make_a_role_child_of_its_descendant(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $parent = $this->makeRole(['slug' => 'parent-role', 'nama' => 'Parent Role']);
        $child = $this->makeRole([
            'slug' => 'child-role',
            'nama' => 'Child Role',
            'parent_slug' => 'parent-role',
            'sort_order' => 2,
            'warna' => '#10b981',
        ]);

        $this->withToken($this->tokenFor($owner))
            ->patchJson('/api/permissions/roles/'.$parent->slug, [
                'nama' => 'Parent Role',
                'parent_slug' => $child->slug,
                'warna' => '#e11d48',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_slug');

        $this->assertDatabaseHas('roles', ['slug' => $parent->slug, 'parent_slug' => 'admin']);
    }

    public function test_owner_can_swap_a_role_with_its_direct_child(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $parent = $this->makeRole([
            'slug' => 'swap-parent', 'nama' => 'Swap Parent', 'warna' => '#2563eb',
        ]);
        $child = $this->makeRole([
            'slug' => 'swap-child', 'nama' => 'Swap Child', 'parent_slug' => 'swap-parent',
            'sort_order' => 2, 'warna' => '#10b981',
        ]);

        $this->withToken($this->tokenFor($owner))
            ->postJson('/api/permissions/reorder', [
                'swap' => [$parent->slug, $child->slug],
                'roles' => [
                    ['slug' => $parent->slug, 'parent_slug' => $child->slug],
                    ['slug' => $child->slug, 'parent_slug' => 'admin'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('roles', ['slug' => $parent->slug, 'parent_slug' => $child->slug]);
        $this->assertDatabaseHas('roles', ['slug' => $child->slug, 'parent_slug' => 'admin']);
    }

    public function test_non_owner_tidak_bisa_membuat_atau_mengubah_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);

        $this->withToken($token)
            ->postJson('/api/permissions/roles', [
                'nama' => 'Role Baru',
                'parent_slug' => 'reception',
                'warna' => '#2563eb',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->patchJson('/api/permissions/roles/reception', [
                'nama' => 'Dicoba Diubah',
                'parent_slug' => 'admin',
                'warna' => '#2563eb',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['slug' => 'role-baru']);
    }

    public function test_permission_owner_selalu_penuh_dan_tidak_bisa_diubah(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        // Menjadikan Owner sebagai target harus ditolak, baik untuk
        // permission maupun untuk data role-nya.
        $this->withToken($this->tokenFor($owner))
            ->putJson('/api/permissions', ['role' => 'owner', 'permissions' => []])
            ->assertForbidden();

        $this->withToken($this->tokenFor($owner))
            ->patchJson('/api/permissions/roles/owner', [
                'nama' => 'Owner Baru',
                'parent_slug' => 'admin',
                'warna' => '#111111',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['slug' => 'owner', 'nama' => 'Master/Owner']);
    }
}
