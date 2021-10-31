<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

class IDCaster
{
    public static function wrap(mixed $value): Wrapper
    {
        return new Wrapper($value);
    }
}
