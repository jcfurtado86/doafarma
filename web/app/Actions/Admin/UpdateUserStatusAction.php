<?php

declare(strict_types = 1);

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;

class UpdateUserStatusAction
{
    public function execute(User $user, UserStatus $status, User $admin): User
    {
        $user->update([
            'status'            => $status,
            'status_changed_at' => now(),
            'status_changed_by' => $admin->id,
        ]);

        return $user->fresh();
    }
}
