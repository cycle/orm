<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\ParentClassRelationsTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ParentClassRelationsTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
