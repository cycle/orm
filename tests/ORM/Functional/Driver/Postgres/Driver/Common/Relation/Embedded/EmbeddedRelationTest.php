<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class EmbeddedRelationTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
