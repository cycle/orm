<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Morphed;

/**
 * @group driver
 * @group driver-postgres
 */
class MorphedHasOneRelationTest extends \Cycle\ORM\Tests\Functional\Relation\Morphed\MorphedHasOneRelationTest
{
    public const DRIVER = 'postgres';
}
