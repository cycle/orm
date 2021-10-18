<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\ManyToManyTest
{
    public const DRIVER = 'postgres';
}
