<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation;

/**
 * @group driver
 * @group driver-postgres
 */
class DeepCyclicTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\DeepCyclicTest
{
    public const DRIVER = 'postgres';
}
