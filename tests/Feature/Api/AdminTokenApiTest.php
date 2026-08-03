<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TokenAbility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('lists tokens for an admin with pagination meta', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create(['name' => 'Token Owner', 'email' => 'owner@example.com']);
    $user->assignRole(Role::User);
    $user->createToken('user-device', [TokenAbility::ProfileRead->value]);

    $response = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'abilities',
                    'last_used_at',
                    'expires_at',
                    'created_at',
                    'user' => ['id', 'name', 'email'],
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

    expect($response->json('meta.current_page'))->toBe(1)
        ->and($response->json('meta.per_page'))->toBe(15)
        ->and($response->json('meta.total'))->toBe(2);

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('admin-session')
        ->and($names)->toContain('user-device');

    $ownerToken = collect($response->json('data'))->firstWhere('name', 'user-device');

    expect($ownerToken['user'])->toMatchArray([
        'id' => $user->id,
        'name' => 'Token Owner',
        'email' => 'owner@example.com',
    ]);
});

it('paginates tokens with per_page and page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create();
    $user->assignRole(Role::User);

    foreach (range(1, 5) as $i) {
        $user->createToken("device-{$i}", [TokenAbility::ProfileRead->value]);
    }

    $page1 = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?per_page=2&page=1')
        ->assertOk();

    expect($page1->json('meta.per_page'))->toBe(2)
        ->and($page1->json('meta.current_page'))->toBe(1)
        ->and($page1->json('meta.total'))->toBe(6)
        ->and($page1->json('meta.last_page'))->toBe(3)
        ->and($page1->json('data'))->toHaveCount(2);

    $page2 = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?per_page=2&page=2')
        ->assertOk();

    expect($page2->json('meta.current_page'))->toBe(2)
        ->and($page2->json('data'))->toHaveCount(2);

    $page1Ids = collect($page1->json('data'))->pluck('id');
    $page2Ids = collect($page2->json('data'))->pluck('id');

    expect($page1Ids->intersect($page2Ids))->toBeEmpty();
});

it('filters tokens by user_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $user->createToken('user-device', [TokenAbility::ProfileRead->value]);

    $other = User::factory()->create();
    $other->assignRole(Role::User);
    $other->createToken('other-device', [TokenAbility::ProfileRead->value]);

    $response = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?user_id='.$user->id)
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($response->json('meta.total'))->toBe(1)
        ->and($names)->toContain('user-device')
        ->and($names)->not->toContain('other-device')
        ->and($names)->not->toContain('admin-session');
});

it('filters tokens by search across name and owner', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create(['name' => 'Alice Owner', 'email' => 'alice@example.com']);
    $user->assignRole(Role::User);
    $user->createToken('mobile-app', [TokenAbility::ProfileRead->value]);

    $other = User::factory()->create(['name' => 'Bob Other', 'email' => 'bob@example.com']);
    $other->assignRole(Role::User);
    $other->createToken('desktop-app', [TokenAbility::ProfileRead->value]);

    $byEmail = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?search=alice@example.com')
        ->assertOk();

    expect(collect($byEmail->json('data'))->pluck('name')->all())->toBe(['mobile-app']);

    $byTokenName = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?q=desktop')
        ->assertOk();

    expect(collect($byTokenName->json('data'))->pluck('name')->all())->toBe(['desktop-app']);
});

it('filters tokens by name and ability', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $user->createToken('profile-reader', [TokenAbility::ProfileRead->value]);
    $user->createToken('profile-writer', [TokenAbility::ProfileWrite->value]);

    $byName = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?name=profile-read')
        ->assertOk();

    expect(collect($byName->json('data'))->pluck('name')->all())->toBe(['profile-reader']);

    $byAbility = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?ability='.TokenAbility::ProfileWrite->value)
        ->assertOk();

    $names = collect($byAbility->json('data'))->pluck('name');

    expect($names)->toContain('profile-writer')
        ->and($names)->not->toContain('profile-reader');
});

it('filters tokens by expired status', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create();
    $user->assignRole(Role::User);

    $active = $user->createToken('active-device', [TokenAbility::ProfileRead->value])->accessToken;
    $active->forceFill(['expires_at' => now()->addDay()])->save();

    $expired = $user->createToken('expired-device', [TokenAbility::ProfileRead->value])->accessToken;
    $expired->forceFill(['expires_at' => now()->subDay()])->save();

    $expiredOnly = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?expired=1')
        ->assertOk();

    expect(collect($expiredOnly->json('data'))->pluck('name')->all())->toBe(['expired-device']);

    $activeOnly = $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?expired=0')
        ->assertOk();

    $activeNames = collect($activeOnly->json('data'))->pluck('name');

    expect($activeNames)->toContain('active-device')
        ->and($activeNames)->toContain('admin-session')
        ->and($activeNames)->not->toContain('expired-device');
});

it('rejects invalid pagination and filter params', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);

    $this->withToken($adminToken)
        ->getJson('/api/v1/admin/tokens?user_id=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);
});

it('forbids listing admin tokens for non-admins even with tokens.manage', function () {
    $role = RoleModel::findOrCreate('token-manager');
    $role->syncPermissions([Permission::TokensManage->value]);

    $user = User::factory()->create();
    $user->assignRole($role);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    expect($user->can(Permission::TokensManage->value))->toBeTrue()
        ->and($user->hasRole(Role::Admin))->toBeFalse();

    $this->withToken($token)
        ->getJson('/api/v1/admin/tokens')
        ->assertForbidden();
});

it('revokes any user token for an admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $target = $user->createToken('user-device', [TokenAbility::ProfileRead->value])->accessToken;

    $this->withToken($adminToken)
        ->deleteJson('/api/v1/admin/tokens/'.$target->id)
        ->assertOk()
        ->assertJsonPath('message', 'Token revoked successfully.');

    expect(PersonalAccessToken::query()->whereKey($target->id)->exists())->toBeFalse();
});

it('forbids revoking tokens via admin API for non-admins even with tokens.manage', function () {
    $role = RoleModel::findOrCreate('token-manager');
    $role->syncPermissions([Permission::TokensManage->value]);

    $user = User::factory()->create();
    $user->assignRole($role);
    $token = $user->createToken('api-login', $user->availableTokenAbilities())->plainTextToken;

    $other = User::factory()->create();
    $other->assignRole(Role::User);
    $target = $other->createToken('other-device', [TokenAbility::ProfileRead->value])->accessToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/admin/tokens/'.$target->id)
        ->assertForbidden();

    expect(PersonalAccessToken::query()->whereKey($target->id)->exists())->toBeTrue();
});

it('returns 404 when revoking a missing token', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $adminToken = $admin->createToken('admin-session', $admin->availableTokenAbilities())->plainTextToken;

    $this->withToken($adminToken)
        ->deleteJson('/api/v1/admin/tokens/999999')
        ->assertNotFound();
});
