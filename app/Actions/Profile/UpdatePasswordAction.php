<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UpdatePasswordAction
{
    /**
     * @param  array{current_password: string, password: string}  $data
     */
    public function execute(User $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        $this->revokeOtherTokens($user);
    }

    /**
     * Invalidate every token except the one that performed the change,
     * so a compromise-driven password update kills stolen sessions.
     */
    private function revokeOtherTokens(User $user): void
    {
        $current = $user->currentAccessToken();
        $query = $user->tokens();

        if ($current instanceof PersonalAccessToken) {
            $query->whereKeyNot($current->getKey());
        }

        $query->delete();
    }
}
