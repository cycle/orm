<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class RelationWithColumnAliasTest extends \Cycle\ORM\Tests\Functional\Relation\RelationWithColumnAliasTest
{
    public const DRIVER = 'sqlite';
}
