<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyScopedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopedTest
{
    public const DRIVER = 'sqlite';
}
