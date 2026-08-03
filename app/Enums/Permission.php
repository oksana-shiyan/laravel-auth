<?php

namespace App\Enums;

enum Permission: string
{
    case ProfileRead = 'profile.read';
    case ProfileWrite = 'profile.write';
    case TokensManage = 'tokens.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
