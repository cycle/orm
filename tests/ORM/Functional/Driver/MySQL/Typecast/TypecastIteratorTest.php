<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastIteratorTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class TypecastIteratorTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
