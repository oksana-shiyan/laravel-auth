<?php

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUserAction
{
    public function execute(User $user, ?PersonalAccessToken $token = null): void
    {
        $current = $token ?? $user->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
