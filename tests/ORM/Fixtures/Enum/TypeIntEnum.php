<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures\Enum;

enum TypeIntEnum: int
{
    case Guest = 0;
    case User = 1;
    case Admin = 2;

    public static function make(int|string $value): ?self
    {
        return self::tryFrom((int)$value);
    }
}
