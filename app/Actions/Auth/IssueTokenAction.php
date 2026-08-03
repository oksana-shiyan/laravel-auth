<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class IssueTokenAction
{
    /**
     * @param  list<string>  $abilities
     */
    public function execute(
        User $user,
        string $name,
        array $abilities,
        bool $rotateExisting = false,
    ): NewAccessToken {
        if ($abilities === []) {
            throw ValidationException::withMessages([
                'abilities' => ['No valid abilities available for this user.'],
            ]);
        }

        if ($rotateExisting) {
            $user->tokens()->where('name', $name)->delete();
        }

        $expiresAt = now()->addDays((int) config('api.token_expiration_days'));

        return $user->createToken($name, $abilities, $expiresAt);
    }
}
