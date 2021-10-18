<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicManyToManyTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclicManyToManyTest
{
    public const DRIVER = 'postgres';
}
