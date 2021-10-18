<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\RefersTo;

/**
 * @group driver
 * @group driver-postgres
 */
class RefersToRelationCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\RefersTo\RefersToRelationCompositeKeyTest
{
    public const DRIVER = 'postgres';
}
