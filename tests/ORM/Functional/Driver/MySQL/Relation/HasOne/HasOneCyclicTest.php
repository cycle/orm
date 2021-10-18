<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasOne;

/**
 * @group driver
 * @group driver-mysql
 */
class HasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCyclicTest
{
    public const DRIVER = 'mysql';
}
