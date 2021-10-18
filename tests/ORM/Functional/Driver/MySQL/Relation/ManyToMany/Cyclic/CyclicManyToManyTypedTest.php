<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicManyToManyTypedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\Cyclic\CyclicManyToManyTypedTest
{
    public const DRIVER = 'mysql';
}
