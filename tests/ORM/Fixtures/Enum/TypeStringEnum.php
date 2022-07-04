<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\Enum;

enum TypeStringEnum: string
{
    case Guest = 'guest';
    case User = 'user';
    case Admin = 'admin';

    public static function make(int|string $value): ?self
    {
        return self::tryFrom((string)$value);
    }
}
