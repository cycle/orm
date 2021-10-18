<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclicManyToManyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class CyclicManyToManyTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
