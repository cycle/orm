<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Morphed;

/**
 * @group driver
 * @group driver-postgres
 */
class MorphedHasManyScopeTest extends \Cycle\ORM\Tests\Functional\Relation\Morphed\MorphedHasManyScopeTest
{
    public const DRIVER = 'postgres';
}
