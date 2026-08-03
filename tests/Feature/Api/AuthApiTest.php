<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TokenAbility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('registers a user and returns a sanctum token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'email', 'roles', 'permissions'], 'token', 'abilities', 'expires_at'],
        ]);

    $user = User::query()->where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(Role::User))->toBeTrue()
        ->and($user->tokens)->toHaveCount(1)
        ->and($response->json('data.abilities'))->not->toBeEmpty();
});

it('does not mint a token on register when abilities would be empty', function () {
    RoleModel::findByName(Role::User->value)->syncPermissions([]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->postJson('/api/v1/register', [
        'name' => 'No Abilities',
        'email' => 'no-abilities@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities']);

    expect(User::query()->where('email', 'no-abilities@example.com')->exists())->toBeFalse();
});

it('logs in, rotates tokens with the same name, and respects ability subsets', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Password1!',
    ]);
    $user->assignRole(Role::User);

    $first = $this->postJson('/api/v1/login', [
        'email' => 'login@example.com',
        'password' => 'Password1!',
        'device_name' => 'cli',
    ])->assertOk();

    $firstToken = $first->json('data.token');

    $second = $this->postJson('/api/v1/login', [
        'email' => 'login@example.com',
        'password' => 'Password1!',
        'device_name' => 'cli',
        'abilities' => [TokenAbility::ProfileRead->value],
    ])->assertOk();

    expect($user->fresh()->tokens()->where('name', 'cli')->count())->toBe(1)
        ->and($second->json('data.abilities'))->toBe([TokenAbility::ProfileRead->value]);

    $this->withToken($firstToken)
        ->getJson('/api/v1/user')
        ->assertUnauthorized();
});

it('does not mint all permission abilities when the user has none', function () {
    $user = User::factory()->create([
        'email' => 'noperms@example.com',
        'password' => 'Password1!',
    ]);

    expect($user->resolveTokenAbilities())->toBe([])
        ->and($user->resolveTokenAbilities([TokenAbility::TokensManage->value]))->toBe([]);

    $this->postJson('/api/v1/login', [
        'email' => 'noperms@example.com',
        'password' => 'Password1!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities']);

    expect($user->fresh()->tokens)->toHaveCount(0);
});

it('rejects login when requested abilities resolve to an empty set', function () {
    $user = User::factory()->create([
        'email' => 'subset@example.com',
        'password' => 'Password1!',
    ]);
    $user->assignRole(Role::User);

    $this->postJson('/api/v1/login', [
        'email' => 'subset@example.com',
        'password' => 'Password1!',
        'abilities' => [TokenAbility::AdminRead->value],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities']);

    expect($user->fresh()->tokens)->toHaveCount(0);
});

it('rejects token create when no valid abilities are available', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/tokens', [
            'name' => 'empty-bot',
            'abilities' => [TokenAbility::AdminRead->value],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities']);
});

it('logs out by revoking the current token', function () {
    $user = User::factory()->create(['password' => 'Password1!']);
    $user->assignRole(Role::User);

    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    expect($user->fresh()->tokens)->toHaveCount(0);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.roles.0', Role::User->value);
});

it('updates profile when user permission and token ability allow it', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', [
        TokenAbility::ProfileWrite->value,
        TokenAbility::ProfileRead->value,
        TokenAbility::TokensManage->value,
    ])->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/v1/profile', [
            'name' => 'Updated Name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('clears email verification on email change and locks verified routes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    expect($user->hasVerifiedEmail())->toBeTrue();

    $this->withToken($token)
        ->patchJson('/api/v1/profile', [
            'email' => 'new@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.email_verified_at', null);

    $user = $user->fresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->withToken($token)
        ->getJson('/api/v1/email/verification-status')
        ->assertConflict()
        ->assertJsonPath('message', 'Email address is not verified.');

    $this->withToken($token)
        ->getJson('/api/v1/tokens')
        ->assertForbidden();

    $this->withToken($token)
        ->patchJson('/api/v1/profile', [
            'name' => 'Still Locked',
        ])
        ->assertForbidden();
});

it('does not clear verification when email is unchanged', function () {
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => now(),
    ]);
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', [
        TokenAbility::ProfileWrite->value,
        TokenAbility::ProfileRead->value,
    ])->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/v1/profile', [
            'name' => 'Renamed',
            'email' => 'same@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects profile update when token lacks ability even if user has permission', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);

    expect($user->can(Permission::ProfileWrite->value))->toBeTrue();

    $token = $user->createToken('readonly', [TokenAbility::ProfileRead->value])->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/v1/profile', [
            'name' => 'Nope',
        ])
        ->assertForbidden();
});

it('manages personal access tokens', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $created = $this->withToken($token)
        ->postJson('/api/v1/tokens', [
            'name' => 'ci-bot',
            'abilities' => [TokenAbility::ProfileRead->value],
        ])
        ->assertCreated();

    $tokenId = $created->json('data.access_token.id');

    $this->withToken($token)
        ->getJson('/api/v1/tokens')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->withToken($token)
        ->deleteJson('/api/v1/tokens/'.$tokenId)
        ->assertOk();

    expect(PersonalAccessToken::query()->whereKey($tokenId)->exists())->toBeFalse();
});

it('revokes all tokens via destroyAll', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);

    $currentToken = $user->createToken('current-session', $user->availableTokenAbilities())->plainTextToken;
    $other = $user->createToken('other-device', $user->availableTokenAbilities());
    $third = $user->createToken('ci-bot', [TokenAbility::ProfileRead->value]);

    expect($user->fresh()->tokens)->toHaveCount(3);

    $this->withToken($currentToken)
        ->deleteJson('/api/v1/tokens')
        ->assertOk()
        ->assertJsonPath('message', 'All tokens revoked successfully.');

    expect($user->fresh()->tokens)->toHaveCount(0)
        ->and(PersonalAccessToken::query()->whereKey($other->accessToken->id)->exists())->toBeFalse()
        ->and(PersonalAccessToken::query()->whereKey($third->accessToken->id)->exists())->toBeFalse();

    $this->withToken($currentToken)
        ->getJson('/api/v1/user')
        ->assertUnauthorized();
});

it('updates password and revokes other tokens', function () {
    $user = User::factory()->create(['password' => 'Password1!']);
    $user->assignRole(Role::User);

    $currentToken = $user->createToken('current-session', $user->availableTokenAbilities())->plainTextToken;
    $stolen = $user->createToken('stolen-device', $user->availableTokenAbilities());

    $this->withToken($currentToken)
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'Password1!',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Password updated successfully.');

    expect($user->fresh()->tokens)->toHaveCount(1)
        ->and(PersonalAccessToken::query()->whereKey($stolen->accessToken->id)->exists())->toBeFalse();

    $this->withToken($currentToken)
        ->getJson('/api/v1/user')
        ->assertOk();

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'NewPassword1!',
    ])->assertOk();
});

it('sends and verifies email through frontend landing url', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $user->assignRole(Role::User);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->actionUrl)
            ->toContain('/verify-email?verify_url=')
            ->toContain(urlencode('/api/v1/email/verify/'));

        return true;
    });

    $url = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('returns the same forgot-password response for unknown emails', function () {
    Notification::fake();

    $user = User::factory()->create();

    $known = $this->postJson('/api/v1/forgot-password', [
        'email' => $user->email,
    ]);

    $unknown = $this->postJson('/api/v1/forgot-password', [
        'email' => 'missing@example.com',
    ]);

    $known->assertOk();
    $unknown->assertOk()
        ->assertExactJson($known->json());

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('resets a password and revokes tokens', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);
    $user->assignRole(Role::User);
    $user->createToken('old-token', $user->availableTokenAbilities());

    $this->postJson('/api/v1/forgot-password', [
        'email' => 'reset@example.com',
    ])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->actionUrl)
            ->toContain('/reset-password?token=')
            ->toContain('email=reset%40example.com');

        $this->postJson('/api/v1/reset-password', [
            'email' => 'reset@example.com',
            'token' => $notification->token,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertOk();

        return true;
    });

    expect($user->fresh()->tokens)->toHaveCount(0);

    $this->postJson('/api/v1/login', [
        'email' => 'reset@example.com',
        'password' => 'NewPassword1!',
    ])->assertOk();
});
