<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyLoadingTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyLoadingTest
{
    public const DRIVER = 'sqlite';
}
