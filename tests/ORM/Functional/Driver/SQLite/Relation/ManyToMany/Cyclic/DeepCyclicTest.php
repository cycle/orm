<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\DeepCyclicTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeepCyclicTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
