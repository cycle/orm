<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasOne;

/**
 * @group driver
 * @group driver-postgres
 */
class HasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCyclicTest
{
    public const DRIVER = 'postgres';
}
