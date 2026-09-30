<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->roleModel()?->slug === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->roleModel()?->slug === 'admin';
    }

    public function isReception(): bool
    {
        return $this->roleModel()?->slug === 'reception';
    }

    public function permissions()
    {
        return Permission::query()
            ->select('permissions.*')
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role', $this->getRawOriginal('role'));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isOwner() || $this->permissions()->where('nama_permission', $permission)->exists();
    }

    /**
     * Daftar nama permission milik user dalam bentuk array string.
     *
     * Dipakai oleh endpoint /api/auth/me supaya SPA bisa menyembunyikan menu
     * dan memblokir route di sisi client. Ini murni untuk UX -- otoritas
     * tetap ada di middleware `permission:` pada backend.
     */
    public function permissionSlugs(): array
    {
        if ($this->isOwner()) {
            return Permission::query()->pluck('nama_permission')->all();
        }

        return $this->permissions()->pluck('permissions.nama_permission')->all();
    }

    public function canManageRole(Role|UserRole $targetRole): bool
    {
        if ($targetRole instanceof UserRole) {
            $targetRole = Role::find($targetRole->value);
        }

        if (! $targetRole) {
            return false;
        }

        $actorRole = $this->roleModel();

        return $this->isOwner()
            || ($actorRole && $this->hasPermission('manage_permissions') && $targetRole->isDescendantOf($actorRole));
    }

    public function assignablePermissions(): Collection
    {
        if ($this->isOwner()) {
            return Permission::query()->get();
        }

        return $this->permissions()->get();
    }

    public function getRoleAttribute(?string $value): ?Role
    {
        return $value ? Role::find($value) : null;
    }

    public function roleModel(): ?Role
    {
        return Role::find($this->getRawOriginal('role'));
    }

    public function setRoleAttribute(Role|string|UserRole $value): void
    {
        $this->attributes['role'] = $value instanceof Role ? $value->slug : ($value instanceof UserRole ? $value->value : $value);
    }
}
