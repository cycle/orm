<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyRelationTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyRelationTest
{
    public const DRIVER = 'sqlite';
}
