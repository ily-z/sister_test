<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('users.view') || $actor->hasPermission('manage_users');
    }

    public function view(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('users.create') || $actor->hasPermission('manage_users');
    }

    public function update(User $actor, User $user): bool
    {
        if ((! $actor->hasPermission('users.edit') && ! $actor->hasPermission('manage_users')) || ! $actor->canManageRole($user->role)) {
            return false;
        }

        return $actor->id !== $user->id || $actor->isOwner();
    }

    public function delete(User $actor, User $user): bool
    {
        if ((! $actor->hasPermission('users.delete') && ! $actor->hasPermission('manage_users')) || ! $actor->canManageRole($user->role)) {
            return false;
        }

        // Prevent deleting yourself.
        if ($actor->id === $user->id) {
            return false;
        }

        return true;
    }

    public function changeRole(User $actor, User $user): bool
    {
        return ($actor->hasPermission('users.edit') || $actor->hasPermission('manage_users')) && $actor->canManageRole($user->role);
    }

    public function assignOwnerRole(User $actor): bool
    {
        return $actor->isOwner();
    }

    public function managePermissions(User $actor): bool
    {
        return $actor->hasPermission('manage_permissions');
    }
}

