<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RelationWithColumnAliasTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RelationWithColumnAliasTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
