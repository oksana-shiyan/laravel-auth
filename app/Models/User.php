<?php

namespace App\Models;

use App\Enums\TokenAbility;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Abilities this user may grant to a token (intersection of Spatie permissions).
     *
     * @return list<string>
     */
    public function availableTokenAbilities(): array
    {
        return TokenAbility::forPermissions(
            $this->getAllPermissions()->pluck('name')
        );
    }

    /**
     * Filter requested abilities down to what the user is allowed to grant.
     *
     * @param  list<string>|null  $requested
     * @return list<string>
     */
    public function resolveTokenAbilities(?array $requested = null): array
    {
        $available = $this->availableTokenAbilities();

        if ($requested === null || $requested === []) {
            return $available;
        }

        return array_values(array_intersect($requested, $available));
    }
}
