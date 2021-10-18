<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyScopedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopedTest
{
    public const DRIVER = 'mysql';
}
