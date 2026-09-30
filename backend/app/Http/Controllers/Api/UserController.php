<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Manajemen user. Logika bisnis & Policy identik dengan versi Blade
 * (app/Http/Controllers/UserController.php), hanya return-nya JSON.
 *
 * Catatan penting untuk SPA:
 *   - Semua pesan sukses dikembalikan sebagai `{message: ...}` dengan
 *     HTTP 200/201, bukan redirect + flash.
 *   - Semua error validasi tetap otomatis jadi HTTP 422 dengan bentuk
 *     `{message, errors}`, jadi frontend cukup satu interceptor.
 */
class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Role yang boleh dipilih actor saat ini (owner disembunyikan dari
     * dropdown standar karena hanya boleh ada satu).
     */
    private function selectableRoles(): array
    {
        $actor = request()->user();

        return Role::query()->orderBy('sort_order')->get()
            ->filter(fn (Role $role) => $role->slug !== 'owner' && $actor->canManageRole($role))
            ->pluck('nama', 'slug')
            ->all();
    }

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('nama')
            ->paginate((int) $request->query('per_page', 10))
            ->withQueryString();

        return response()->json([
            'users' => $users,
            'q' => $q,
            'roleOptions' => Role::pluck('nama', 'slug')->all(),
            'selectableRoles' => $this->selectableRoles(),
            'userActivities' => Activity::where('type', 'user')->orderByDesc('created_at')->take(8)->get(),
        ]);
    }

    public function create(): JsonResponse
    {
        return response()->json(['roleOptions' => $this->selectableRoles()]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'exists:roles,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validated['role'] === 'owner') {
            throw new HttpException(403, 'Owner hanya boleh ada satu dan tidak bisa ditambahkan secara manual.');
        }

        abort_unless($actor->canManageRole(Role::findOrFail($validated['role'])), 403);

        $validated['is_active'] = $request->boolean('is_active', true);

        $createdUser = User::create($validated);

        $this->logUserActivity(
            'Akun user dibuat',
            "Akun {$createdUser->nama} ({$createdUser->email}) dibuat dengan role {$createdUser->role->label()}.",
            'person_add',
            'purple'
        );

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'user' => $this->userPayload($createdUser),
        ], 201);
    }

    public function edit(User $user): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($user),
            'roleOptions' => $this->selectableRoles(),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'exists:roles,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validated['role'] === 'owner' && ! $user->isOwner()) {
            throw new HttpException(403, 'Owner hanya boleh satu. Gunakan fitur transfer kepemilikan di halaman list user.');
        }

        abort_unless($request->user()->canManageRole(Role::findOrFail($validated['role'])), 403);

        $validated['is_active'] = $request->boolean('is_active', false);

        if ($user->isOwner() && ! $validated['is_active']) {
            throw new HttpException(422, 'Role Owner tidak boleh dinonaktifkan.');
        }

        $original = $user->getOriginal();
        $originalStatus = $original['is_active'] ? 'Aktif' : 'Nonaktif';

        // Kolom role bisa berupa enum UserRole pada record lama, atau
        // sudah berupa string slug. Samakan keduanya ke slug.
        $originalRole = $original['role'] instanceof UserRole
            ? $original['role']->value
            : $original['role'];
        $originalRoleLabel = Role::find($originalRole)?->nama ?? $originalRole;
        $originalRoleValue = is_object($original['role']) ? $original['role']->value : $original['role'];

        if ($request->filled('password')) {
            $passwordData = $request->validate([
                'password' => ['string', 'min:6', 'confirmed'],
            ]);
            $validated['password'] = $passwordData['password'];
        }

        $user->update($validated);

        $changes = $this->formatUserChanges($original, $validated, $user, $originalStatus, $originalRoleLabel, $originalRoleValue);

        if ($changes !== '') {
            $this->logUserActivity(
                'Akun user diperbarui',
                "Akun {$original['nama']} ({$original['email']}) diperbarui.\n".$changes,
                'edit',
                'purple'
            );
        }

        /*
         * Kalau user menonaktifkan dirinya sendiri, cabut semua token
         * Sanctum-nya. Versi Blade melakukan session logout; untuk API,
         * menghapus token memberi efek yang sama dan langsung berlaku.
         */
        $selfDeactivated = $request->user()->id === $user->id && ! $validated['is_active'];

        if ($selfDeactivated) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $selfDeactivated
                ? 'Akun Anda telah dinonaktifkan dan akses telah dihentikan.'
                : 'User berhasil diperbarui.',
            'user' => $this->userPayload($user->fresh()),
            'self_deactivated' => $selfDeactivated,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $userName = $user->nama;
        $userEmail = $user->email;

        $user->tokens()->delete();
        $user->delete();

        $this->logUserActivity(
            'Akun user dihapus',
            "Akun {$userName} ({$userEmail}) dihapus oleh ".(request()->user()->nama ?? 'System').'.',
            'delete',
            'purple'
        );

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    /**
     * PATCH /api/users/{user}/role
     *
     * Mengganti role, termasuk transfer kepemilikan Owner.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('changeRole', $user);

        $validated = $request->validate([
            'role' => ['required', 'exists:roles,slug'],
            'new_owner_id' => ['nullable', 'exists:users,id'],
        ]);

        // SKENARIO 1: Owner "turun takhta" dan menurunkan role dirinya sendiri.
        if ($user->isOwner() && $validated['role'] !== 'owner') {
            if (empty($validated['new_owner_id'])) {
                throw new HttpException(422, 'Pilih Owner pengganti terlebih dahulu.');
            }

            $newOwner = User::findOrFail($validated['new_owner_id']);

            DB::transaction(function () use ($user, $newOwner, $validated) {
                $newOwner->update(['role' => 'owner']);
                $user->update(['role' => $validated['role']]);
            });

            $this->logUserActivity(
                'Hak kepemilikan dipindahkan',
                "Kepemilikan Owner dipindahkan dari {$user->nama} ke {$newOwner->nama} oleh ".(request()->user()->nama ?? 'System').'.',
                'swap_horiz',
                'purple'
            );

            return response()->json([
                'message' => 'Hak Kepemilikan (Owner) berhasil ditransfer.',
                'user' => $this->userPayload($user->fresh()),
            ]);
        }

        // SKENARIO 2: Cegah user biasa dipromosikan jadi Owner lewat request palsu.
        if ($validated['role'] === 'owner' && ! $user->isOwner()) {
            throw new HttpException(422, 'Tidak bisa menambah Owner. Sistem hanya mengizinkan 1 Owner.');
        }

        abort_unless($request->user()->canManageRole(Role::findOrFail($validated['role'])), 403);

        // SKENARIO 3: Perubahan role biasa.
        $user->update(['role' => $validated['role']]);

        $this->logUserActivity(
            'Role user diubah',
            "Role {$user->nama} diubah menjadi {$user->role->label()} oleh {$request->user()->nama}.",
            'person',
            'purple'
        );

        return response()->json([
            'message' => 'Role user berhasil diubah.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * Bentuk user untuk response API. Sengaja tidak memakai
     * $user->toArray() supaya hash password tidak pernah ikut.
     */
    private function userPayload(User $user): array
    {
        $role = $user->roleModel();

        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
            'role' => $user->getRawOriginal('role'),
            'role_name' => $role?->nama,
            'role_label' => $role?->label(),
            'role_color' => $role?->color,
            'is_active' => (bool) $user->is_active,
            'is_owner' => $user->isOwner(),
            'permissions' => $user->permissionSlugs(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function logUserActivity(string $title, string $description, string $icon = 'person', string $color = 'purple'): void
    {
        Activity::create([
            'user_id' => request()->user()?->id,
            'user' => request()->user()?->nama ?? 'System',
            'type' => 'user',
            'icon' => $icon,
            'color' => $color,
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function formatUserChanges(array $original, array $validated, User $updatedUser, string $originalStatus, string $originalRoleLabel, string $originalRoleValue): string
    {
        $lines = [];

        if (isset($validated['nama']) && $validated['nama'] !== $original['nama']) {
            $lines[] = "• Nama: {$original['nama']} → {$updatedUser->nama}";
        }

        if (isset($validated['email']) && $validated['email'] !== $original['email']) {
            $lines[] = "• Email: {$original['email']} → {$updatedUser->email}";
        }

        if ($validated['is_active'] !== (bool) $original['is_active']) {
            $newStatus = $validated['is_active'] ? 'Aktif' : 'Nonaktif';
            $lines[] = "• Status: {$originalStatus} → {$newStatus}";
        }

        if (isset($validated['role']) && $validated['role'] !== $originalRoleValue) {
            $newRoleLabel = $updatedUser->role->label();
            $lines[] = "• Role: {$originalRoleLabel} → {$newRoleLabel}";
        }

        if (isset($validated['password'])) {
            $lines[] = '• Password: Diubah';
        }

        return implode("\n", $lines);
    }
}
