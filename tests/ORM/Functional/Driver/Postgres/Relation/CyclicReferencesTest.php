<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation;

/**
 * @group driver
 * @group driver-postgres
 */
class CyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\CyclicReferencesTest
{
    public const DRIVER = 'postgres';
}
