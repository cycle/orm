<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicManyToManyTypedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\Cyclic\CyclicManyToManyTypedTest
{
    public const DRIVER = 'postgres';
}
