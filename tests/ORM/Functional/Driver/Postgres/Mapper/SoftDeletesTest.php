<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Mapper;

/**
 * @group driver
 * @group driver-postgres
 */
class SoftDeletesTest extends \Cycle\ORM\Tests\Functional\Mapper\SoftDeletesTest
{
    public const DRIVER = 'postgres';
}
