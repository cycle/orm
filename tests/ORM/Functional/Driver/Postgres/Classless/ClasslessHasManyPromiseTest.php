<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessHasManyPromiseTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessHasManyPromiseTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
