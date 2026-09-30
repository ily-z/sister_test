<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $primaryKey = 'slug';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['slug', 'nama', 'parent_slug', 'sort_order', 'warna', 'is_system'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role', 'permission_id', 'slug', 'id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_slug', 'slug')->orderBy('sort_order');
    }

    public function isDescendantOf(Role $ancestor): bool
    {
        $current = $this->parent;
        $visited = [];

        while ($current && ! in_array($current->slug, $visited, true)) {
            if ($current->slug === $ancestor->slug) {
                return true;
            }
            $visited[] = $current->slug;
            $current = $current->parent;
        }

        return false;
    }

    public function label(): string
    {
        return $this->nama;
    }

    public function value(): string
    {
        return $this->slug;
    }

    public function getValueAttribute(): string
    {
        return $this->slug;
    }

    public function getLabelAttribute(): string
    {
        return $this->nama;
    }
}