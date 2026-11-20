<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\JsonTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
final class JsonTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
