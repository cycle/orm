<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyScopedPivotTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopedPivotTest
{
    public const DRIVER = 'mysql';
}
