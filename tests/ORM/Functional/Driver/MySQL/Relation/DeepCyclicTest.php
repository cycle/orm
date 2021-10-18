<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation;

/**
 * @group driver
 * @group driver-mysql
 */
class DeepCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\DeepCyclicTest
{
    public const DRIVER = 'mysql';
}
