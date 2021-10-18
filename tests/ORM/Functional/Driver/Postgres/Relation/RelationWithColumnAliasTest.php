<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation;

/**
 * @group driver
 * @group driver-postgres
 */
class RelationWithColumnAliasTest extends \Cycle\ORM\Tests\Functional\Relation\RelationWithColumnAliasTest
{
    public const DRIVER = 'postgres';
}
