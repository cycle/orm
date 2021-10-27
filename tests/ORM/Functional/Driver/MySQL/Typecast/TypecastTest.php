<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class TypecastTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
