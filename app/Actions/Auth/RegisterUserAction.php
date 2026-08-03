<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

class RegisterUserAction
{
    public function __construct(
        private readonly IssueTokenAction $issueToken,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: NewAccessToken}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole(Role::User);

            event(new Registered($user));

            $token = $this->issueToken->execute(
                $user,
                config('api.login_token_name'),
                $user->availableTokenAbilities(),
                rotateExisting: false,
            );

            return [
                'user' => $user->fresh(['roles', 'permissions']),
                'token' => $token,
            ];
        });
    }
}
