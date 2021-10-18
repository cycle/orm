<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyPromiseEagerLoadTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyPromiseEagerLoadTest
{
    public const DRIVER = 'mysql';
}
