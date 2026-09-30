<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    // Helper untuk membuang role Owner dari dropdown standar
    private function getSelectableRoles()
    {
        $actor = auth()->user();

        return Role::query()->orderBy('sort_order')->get()
            ->filter(fn (Role $role) => $role->slug !== 'owner' && $actor->canManageRole($role))
            ->pluck('nama', 'slug')
            ->all();
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query
                    ->where('nama', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'q' => $q,
            'roleOptions' => Role::pluck('nama', 'slug')->all(), // Untuk label text biasa
            'selectableRoles' => $this->getSelectableRoles(), // Untuk opsi dropdown
            'userActivities' => Activity::where('type', 'user')->orderByDesc('created_at')->take(8)->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roleOptions' => $this->getSelectableRoles(),
        ]);
    }

    public function store(Request $request)
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
            abort(403, 'Owner hanya boleh ada satu dan tidak bisa ditambahkan secara manual.');
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

        return redirect()->route('admin.users.index')->with('status', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roleOptions' => $this->getSelectableRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'exists:roles,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validated['role'] === 'owner' && ! $user->isOwner()) {
            abort(403, 'Owner hanya boleh satu. Gunakan fitur transfer kepemilikan di halaman list user.');
        }

        abort_unless($request->user()->canManageRole(Role::findOrFail($validated['role'])), 403);

        $validated['is_active'] = $request->boolean('is_active', false);

        if ($user->isOwner() && ! $validated['is_active']) {
            return back()->withErrors(['is_active' => 'Role Owner tidak boleh dinonaktifkan.']);
        }

        $original = $user->getOriginal();
        $originalStatus = $original['is_active'] ? 'Aktif' : 'Nonaktif';
        $originalRole = $original['role'];
        if ($originalRole instanceof UserRole) {
            $originalRole = $originalRole->value;
        }
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
                "Akun {$original['nama']} ({$original['email']}) diperbarui.\n" . $changes,
                'edit',
                'purple'
            );
        }

        if ($request->user()->id === $user->id && ! $validated['is_active']) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('status', 'Akun Anda telah dinonaktifkan dan akses telah dihentikan.');
        }

        return redirect()->route('admin.users.index')->with('status', 'User berhasil diperbarui.');
    }

    private function logUserActivity(string $title, string $description, string $icon = 'person', string $color = 'purple')
    {
        Activity::create([
            'user_id' => auth()->id(),
            'user' => auth()->user()->nama ?? 'System',
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

    public function destroy(User $user)
    {
        $userName = $user->nama;
        $userEmail = $user->email;
        $user->delete();

        $this->logUserActivity(
            'Akun user dihapus',
            "Akun {$userName} ({$userEmail}) dihapus oleh " . (auth()->user()->nama ?? 'System') . ".",
            'delete',
            'purple'
        );

        return redirect()->route('admin.users.index')->with('status', 'User berhasil dihapus.');
    }

    public function updateRole(Request $request, User $user)
    {
        $this->authorize('changeRole', $user);

        $validated = $request->validate([
            'role' => ['required', 'exists:roles,slug'],
            'new_owner_id' => ['nullable', 'exists:users,id'], // ID owner pengganti
        ]);

        // SKENARIO 1: Owner sedang "turun takhta" dan ganti role dirinya sendiri
        if ($user->isOwner() && $validated['role'] !== 'owner') {
            if (empty($validated['new_owner_id'])) {
                return back()->withErrors(['role' => 'Pilih Owner pengganti terlebih dahulu.']);
            }

            $newOwner = User::findOrFail($validated['new_owner_id']);

            // Gunakan Transaction agar jika error, data tidak setengah berubah
            DB::transaction(function () use ($user, $newOwner, $validated) {
                // 1. Jadikan user yang dipilih sebagai Owner baru
                $newOwner->update(['role' => 'owner']);
                // 2. Turunkan role Owner saat ini menjadi role yang dipilih (misal Admin)
                $user->update(['role' => $validated['role']]);
            });

            $this->logUserActivity(
                'Hak kepemilikan dipindahkan',
                "Kepemilikan Owner dipindahkan dari {$user->nama} ke {$newOwner->nama} oleh " . (auth()->user()->nama ?? 'System') . ".",
                'swap_horiz',
                'purple'
            );

            return back()->with('status', 'Hak Kepemilikan (Owner) berhasil ditransfer.');
        }

        // SKENARIO 2: Mencegah perubahan user biasa menjadi Owner secara paksa lewat request palsu
        if ($validated['role'] === 'owner' && ! $user->isOwner()) {
            return back()->withErrors(['role' => 'Tidak bisa menambah Owner. Sistem hanya mengizinkan 1 Owner.']);
        }

        abort_unless($request->user()->canManageRole(Role::findOrFail($validated['role'])), 403);

        // SKENARIO 3: Perubahan role normal (Owner mengubah Admin -> Reception, dll)
        $user->update([
            'role' => $validated['role'],
        ]);

        $this->logUserActivity(
            'Role user diubah',
            "Role {$user->nama} diubah menjadi {$user->role->label()} oleh {$request->user()->nama}.",
            'person',
            'purple'
        );

        return back()->with('status', 'Role user berhasil diubah.');
    }
}