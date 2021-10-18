<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\RefersTo;

/**
 * @group driver
 * @group driver-sqlite
 */
class RefersToRelationCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\RefersTo\RefersToRelationCompositeKeyTest
{
    public const DRIVER = 'sqlite';
}
