<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\FactoryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class FactoryTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
