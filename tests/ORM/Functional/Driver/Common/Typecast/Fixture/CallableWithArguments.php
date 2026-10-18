<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

final class CallableWithArguments
{
    public static function typed(string $value, int $suffix): string
    {
        return $value . ':' . $suffix;
    }

    public static function untyped(string $value, $suffix): string
    {
        return $value . ':' . $suffix;
    }

    public static function union(string $value, int|string $suffix): string
    {
        return $value . ':' . $suffix;
    }

    public static function single(string $value): string
    {
        return $value;
    }
}
