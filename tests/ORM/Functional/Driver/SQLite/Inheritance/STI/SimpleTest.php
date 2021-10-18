<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlite
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'sqlite';
}
