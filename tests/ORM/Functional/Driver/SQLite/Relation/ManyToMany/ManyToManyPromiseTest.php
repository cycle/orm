<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyPromiseTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyPromiseTest
{
    public const DRIVER = 'sqlite';
}
