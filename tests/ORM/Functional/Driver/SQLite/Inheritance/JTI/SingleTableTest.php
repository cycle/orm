<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\JTI;

/**
 * @group driver
 * @group driver-sqlite
 */
class SingleTableTest extends \Cycle\ORM\Tests\Functional\Inheritance\JTI\SingleTableTest
{
    public const DRIVER = 'sqlite';
}
