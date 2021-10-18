<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicManyToManyTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclicManyToManyTest
{
    public const DRIVER = 'sqlite';
}
