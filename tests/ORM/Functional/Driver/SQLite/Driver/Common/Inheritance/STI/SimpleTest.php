<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlite
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'sqlite';
}
