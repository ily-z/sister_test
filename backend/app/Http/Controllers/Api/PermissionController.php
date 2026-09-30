<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Pengaturan permission & hierarki role.
 *
 * Semua aturan hierarki (parent harus di atas, tidak boleh siklus, owner
 * tidak bisa diedit) identik dengan versi Blade
 * (app/Http/Controllers/PermissionController.php), hanya return-nya JSON.
 */
class PermissionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('managePermissions');

        $actor = $request->user();

        $roles = Role::query()->with('children')->orderBy('sort_order')->get()
            ->filter(fn (Role $role) => $role->slug !== 'owner' && $actor->canManageRole($role))->values();

        abort_if($roles->isEmpty(), 403);

        $selectedRole = Role::find((string) $request->query('role'));
        if (! $selectedRole || ! $roles->contains('slug', $selectedRole->slug)) {
            $selectedRole = $roles->first();
        }

        $allRoles = Role::query()->orderBy('sort_order')->get();

        $permissionGroups = $this->permissionGroups();
        $groupedNames = collect($permissionGroups)->flatMap(fn (array $group) => array_keys($group['permissions']));

        $assignablePermissions = $actor->assignablePermissions();
        if (! $actor->isOwner() && $actor->hasPermission('manage_permissions')) {
            $assignablePermissions = Permission::whereIn('nama_permission', $groupedNames)->get();
        }

        return response()->json([
            'roles' => $roles,
            'allRoles' => $allRoles,
            'roleTree' => $allRoles->whereNull('parent_slug')->values(),
            'permissions' => Permission::query()->orderBy('nama_permission')->get(),
            'permissionGroups' => collect($permissionGroups)->map(function (array $group) {
                $names = array_keys($group['permissions']);
                $group['items'] = Permission::whereIn('nama_permission', $names)->get()
                    ->sortBy(fn (Permission $permission) => array_search($permission->nama_permission, $names, true))
                    ->values();

                return $group;
            })->values(),
            'selectedRole' => $selectedRole,
            'assigned' => $selectedRole->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values(),
            'assignable' => $assignablePermissions->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'canCreate' => $actor->isOwner(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('managePermissions');

        $role = Role::findOrFail((string) $request->query('role'));
        abort_unless($role->slug !== 'owner' && $request->user()->canManageRole($role), 403);

        return response()->json([
            'role' => [
                'slug' => $role->slug,
                'nama' => $role->nama,
                'parent' => $role->parent?->nama ?? 'Root',
                'warna' => $role->warna ?: '#2563eb',
            ],
            'assigned' => $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('managePermissions');

        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,slug'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'warna' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $targetRole = Role::findOrFail($validated['role']);
        abort_if($targetRole->slug === 'owner', 403, 'Permission Owner selalu penuh dan tidak dapat diubah.');
        abort_unless($request->user()->canManageRole($targetRole), 403);

        $selected = collect($validated['permissions'] ?? [])->map(fn ($id) => (int) $id)->unique();

        // Aturan: "view" adalah prasyarat untuk create/edit/delete pada resource yang sama.
        // Berlaku otomatis untuk semua grup berpola "<resource>.view/create/edit/delete",
        // jadi tidak perlu di-hardcode per modul.
        $permissionIdsByName = Permission::query()->pluck('id', 'nama_permission');
        $crudByResource = [];

        foreach ($permissionIdsByName as $name => $id) {
            if (! str_contains($name, '.')) {
                continue;
            }
            $crudByResource[Str::beforeLast($name, '.')][Str::afterLast($name, '.')] = (int) $id;
        }

        foreach ($crudByResource as $actions) {
            if (! isset($actions['view'])) {
                continue;
            }
            $viewId = $actions['view'];
            $crudIds = collect($actions)->except('view')->values();

            if ($crudIds->intersect($selected)->isNotEmpty()) {
                $selected->push($viewId);
            }
            if (! $selected->contains($viewId)) {
                $selected = $selected->diff($crudIds);
            }
        }

        $selected = $selected->unique()->values();

        $visiblePermissionNames = collect($this->permissionGroups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']));
        $visiblePermissionIds = Permission::whereIn('nama_permission', $visiblePermissionNames)->pluck('id');

        $allowed = $request->user()->hasPermission('manage_permissions') && ! $request->user()->isOwner()
            ? $visiblePermissionIds
            : $request->user()->assignablePermissions()->pluck('id');

        abort_if($selected->diff($allowed)->isNotEmpty(), 403, 'Anda hanya dapat memberikan permission yang Anda miliki.');

        if ($request->user()->isOwner() && ! empty($validated['warna'])) {
            $targetRole->update(['warna' => strtolower($validated['warna'])]);
        }

        DB::transaction(function () use ($targetRole, $selected, $visiblePermissionIds) {
            $existing = $targetRole->permissions()->pluck('permissions.id');
            $protected = $existing->diff($visiblePermissionIds);
            $targetRole->permissions()->sync($selected->merge($protected)->unique()->values());
        });

        return response()->json([
            'message' => 'Permission role berhasil diperbarui.',
            'role' => $targetRole->slug,
            'assigned' => $targetRole->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values(),
        ]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'parent_slug' => ['required', 'string', 'exists:roles,slug'],
            'warna' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $slug = Str::slug($data['nama']);
        abort_if($slug === '' || Role::where('slug', $slug)->exists(), 422, 'Nama role sudah digunakan atau tidak valid.');

        $parent = Role::findOrFail($data['parent_slug']);
        abort_if($parent->slug !== 'owner' && ! $request->user()->canManageRole($parent), 403, 'Parent role berada di luar hierarki Anda.');

        $sortOrder = ((int) Role::where('parent_slug', $parent->slug)->max('sort_order')) + 1;

        $role = Role::create([
            'slug' => $slug,
            'nama' => $data['nama'],
            'parent_slug' => $parent->slug,
            'sort_order' => $sortOrder,
            'warna' => strtolower($data['warna']),
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Role baru berhasil dibuat.',
            'role' => $role,
        ], 201);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_if($role->is_system || $role->slug === 'owner', 403, 'Role sistem tidak dapat diedit.');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'parent_slug' => ['required', 'string', 'exists:roles,slug'],
            'warna' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $parent = Role::findOrFail($data['parent_slug']);
        if ($parent->slug === $role->slug || $parent->isDescendantOf($role)) {
            throw ValidationException::withMessages([
                'parent_slug' => 'Parent tidak valid. Pilih role di luar cabang bawahan role ini agar hierarki tidak membentuk siklus.',
            ]);
        }

        $role->update([
            'nama' => $data['nama'],
            'parent_slug' => $parent->slug,
            'warna' => strtolower($data['warna']),
        ]);

        return response()->json([
            'message' => 'Role berhasil diperbarui.',
            'role' => $role->fresh(),
        ]);
    }

    public function destroyRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_if($role->is_system || $role->slug === 'owner', 403, 'Role sistem tidak dapat dihapus.');
        abort_if(User::where('role', $role->slug)->exists(), 422, 'Pindahkan user dengan role ini terlebih dahulu.');

        DB::transaction(function () use ($role) {
            $role->children()->update(['parent_slug' => $role->parent_slug]);
            $role->permissions()->detach();
            $role->delete();
        });

        return response()->json(['message' => 'Role berhasil dihapus.']);
    }

    public function updateColor(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $validated = $request->validate([
            'warna' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        abort_if($role->slug === 'owner', 403, 'Warna Owner tidak dapat diubah.');

        $role->update(['warna' => strtolower($validated['warna'])]);

        return response()->json(['ok' => true, 'warna' => $role->warna]);
    }

    public function reorder(Request $request): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*.slug' => ['required', 'exists:roles,slug'],
            'roles.*.parent_slug' => ['nullable', 'exists:roles,slug'],
            'swap' => ['nullable', 'array', 'size:2'],
            'swap.*' => ['required', 'exists:roles,slug'],
        ]);

        // Kalau frontend hanya mengirim `swap`, `roles` tidak wajib ada.
        if (empty($data['roles']) && empty($data['swap'])) {
            throw new HttpException(422, 'Tidak ada perubahan struktur role yang dikirim.');
        }

        DB::transaction(function () use ($data) {
            if (! empty($data['swap'])) {
                [$firstSlug, $secondSlug] = $data['swap'];
                $first = Role::findOrFail($firstSlug);
                $second = Role::findOrFail($secondSlug);

                abort_if($second->parent_slug !== $first->slug, 422, 'Swap hanya dapat dilakukan dengan child langsung.');
                abort_if($first->slug === 'owner' || $second->slug === 'owner', 422, 'Owner tidak dapat ditukar.');

                $firstParent = $first->parent_slug;
                $first->update(['parent_slug' => $second->slug]);
                $second->update(['parent_slug' => $firstParent]);

                return;
            }

            foreach ($data['roles'] as $position => $roleData) {
                abort_if($roleData['slug'] === 'owner' && $roleData['parent_slug'] !== null, 422, 'Owner harus menjadi root.');
                abort_if($roleData['slug'] === $roleData['parent_slug'], 422, 'Role tidak dapat menjadi parent dirinya sendiri.');

                $role = Role::findOrFail($roleData['slug']);

                if ($roleData['parent_slug']) {
                    $parent = Role::findOrFail($roleData['parent_slug']);
                    abort_if($parent->isDescendantOf($role), 422, 'Struktur role tidak valid karena membentuk siklus.');
                } elseif ($roleData['slug'] !== 'owner') {
                    abort(422, 'Semua role selain Owner harus memiliki parent.');
                }

                Role::where('slug', $roleData['slug'])->update([
                    'parent_slug' => $roleData['parent_slug'],
                    'sort_order' => $position,
                ]);
            }

            Role::where('slug', 'owner')->update(['parent_slug' => null, 'sort_order' => 0]);
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Daftar permission yang ditampilkan di UI, dikelompokkan per modul.
     *
     * Grup "Analitik" (analytics.view) sengaja tidak ada di sini: Dashboard
     * harus bisa diakses semua role, jadi tidak boleh ada toggle yang bisa
     * mematikannya. Permission lama di database tetap aman diabaikan karena
     * tidak dipakai untuk gate apapun (lihat blok "protected" di update()).
     */
    private function permissionGroups(): array
    {
        return [
            ['name' => 'Akses & Role', 'icon' => 'admin_panel_settings', 'permissions' => ['manage_permissions' => 'Kelola permission role bawahan']],
            ['name' => 'Manajemen User', 'icon' => 'group', 'permissions' => ['users.view' => 'Lihat', 'users.create' => 'Tambah', 'users.edit' => 'Edit', 'users.delete' => 'Hapus']],
            ['name' => 'Booking & Pesanan', 'icon' => 'receipt_long', 'permissions' => ['bookings.view' => 'Lihat', 'bookings.create' => 'Tambah', 'bookings.edit' => 'Edit', 'bookings.delete' => 'Hapus']],
            ['name' => 'Armada', 'icon' => 'directions_car', 'permissions' => ['fleet.view' => 'Lihat', 'fleet.create' => 'Tambah', 'fleet.edit' => 'Edit', 'fleet.delete' => 'Hapus']],
            ['name' => 'Keuangan', 'icon' => 'payments', 'permissions' => ['finance.view' => 'Lihat', 'finance.create' => 'Tambah', 'finance.edit' => 'Edit', 'finance.delete' => 'Hapus']],
            ['name' => 'Bengkel & Servis', 'icon' => 'build', 'permissions' => ['service.view' => 'Lihat', 'service.create' => 'Tambah', 'service.edit' => 'Edit', 'service.delete' => 'Hapus']],
        ];
    }
}
