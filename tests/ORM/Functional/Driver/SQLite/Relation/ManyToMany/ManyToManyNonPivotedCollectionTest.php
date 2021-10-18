<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyNonPivotedCollectionTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyNonPivotedCollectionTest
{
    public const DRIVER = 'sqlite';
}
