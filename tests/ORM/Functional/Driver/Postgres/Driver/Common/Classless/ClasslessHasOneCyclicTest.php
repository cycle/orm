<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessHasOneCyclicTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessHasOneCyclicTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
