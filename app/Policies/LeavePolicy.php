<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeavePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_leaves') || $user->hasPermissionTo('view_my_leaves');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Leave $leave): bool
    {
        if ($user->hasPermissionTo('view_leaves')) {
            return true;
        }
        // Users can view their own leaves
        return $user->id === $leave->user_id && $user->hasPermissionTo('view_my_leaves');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_leaves') || $user->hasPermissionTo('request_leaves');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Leave $leave): bool
    {
        // Admin/Manager can edit any leave
        if ($user->hasPermissionTo('edit_leaves') || $user->hasPermissionTo('approve_leaves')) {
            return true;
        }
        // Users can edit their own pending leaves
        return $user->id === $leave->user_id && $leave->status === 'pending' && $user->hasPermissionTo('request_leaves');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Leave $leave): bool
    {
        if ($user->hasPermissionTo('delete_leaves')) {
            return true;
        }
        // Users can delete their own pending leaves
        return $user->id === $leave->user_id && $leave->status === 'pending' && $user->hasPermissionTo('request_leaves');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Leave $leave): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Leave $leave): bool
    {
        return false;
    }
}
