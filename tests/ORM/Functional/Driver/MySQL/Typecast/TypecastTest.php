<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class TypecastTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
