<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Classless;

/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessHasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessHasOneCyclicTest
{
    public const DRIVER = 'postgres';
}
