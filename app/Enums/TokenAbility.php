<?php

namespace App\Enums;

enum TokenAbility: string
{
    case ProfileRead = 'profile.read';
    case ProfileWrite = 'profile.write';
    case TokensManage = 'tokens.manage';
    case AdminRead = 'admin.read';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Map Spatie permissions to allowed Sanctum abilities.
     *
     * @return list<string>
     */
    public static function forPermissions(iterable $permissions): array
    {
        $allowed = [];

        foreach ($permissions as $permission) {
            $name = is_string($permission) ? $permission : $permission->name;

            if (in_array($name, self::values(), true)) {
                $allowed[] = $name;
            }
        }

        return array_values(array_unique($allowed));
    }
}
