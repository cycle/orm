<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicManyToManyTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\Cyclic\CyclicManyToManyTest
{
    public const DRIVER = 'sqlite';
}
