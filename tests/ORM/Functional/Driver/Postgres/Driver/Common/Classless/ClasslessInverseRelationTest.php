<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessInverseRelationTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessInverseRelationTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
