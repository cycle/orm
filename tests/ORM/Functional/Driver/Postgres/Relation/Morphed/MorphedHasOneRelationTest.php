<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasOneRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class MorphedHasOneRelationTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
