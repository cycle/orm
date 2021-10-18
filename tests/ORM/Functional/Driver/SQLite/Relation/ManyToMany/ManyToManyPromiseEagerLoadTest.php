<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyPromiseEagerLoadTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyPromiseEagerLoadTest
{
    public const DRIVER = 'sqlite';
}
