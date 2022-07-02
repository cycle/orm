<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Enum\Fixture;

enum TypeStringEnum: string
{
    case Guest = 'guest';
    case User = 'user';
    case Admin = 'admin';

    public static function make(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
