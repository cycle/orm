<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyDeepenedTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyDeepenedTest
{
    public const DRIVER = 'sqlite';
}
