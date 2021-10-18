<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToRelationCompositeKeyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class RefersToRelationCompositeKeyTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
