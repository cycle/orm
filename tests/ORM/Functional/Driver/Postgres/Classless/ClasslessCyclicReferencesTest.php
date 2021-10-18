<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Classless;

/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessCyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessCyclicReferencesTest
{
    public const DRIVER = 'postgres';
}
