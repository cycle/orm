<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyPromiseEagerLoadTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyPromiseEagerLoadTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
