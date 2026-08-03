<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class LoginUserAction
{
    public function __construct(
        private readonly IssueTokenAction $issueToken,
    ) {}

    /**
     * @param  array{email: string, password: string, device_name?: string, abilities?: list<string>}  $credentials
     * @return array{user: User, token: NewAccessToken}
     */
    public function execute(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $abilities = $user->resolveTokenAbilities($credentials['abilities'] ?? null);

        $token = $this->issueToken->execute(
            $user,
            $credentials['device_name'] ?? config('api.login_token_name'),
            $abilities,
            rotateExisting: true,
        );

        return [
            'user' => $user->load(['roles', 'permissions']),
            'token' => $token,
        ];
    }
}
