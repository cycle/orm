<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI;

/**
 * @group driver
 * @group driver-postgres
 */
class SingleTableTest extends \Cycle\ORM\Tests\Functional\Inheritance\JTI\SingleTableTest
{
    public const DRIVER = 'postgres';
}
