<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyPromiseEagerLoadTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyPromiseEagerLoadTest
{
    public const DRIVER = 'postgres';
}
