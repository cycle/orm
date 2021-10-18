<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

/**
 * @group driver
 * @group driver-postgres
 */
class RenamedPKTest extends \Cycle\ORM\Tests\Functional\RenamedPKTest
{
    public const DRIVER = 'postgres';
}
