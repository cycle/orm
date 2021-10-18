<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\STI;

/**
 * @group driver
 * @group driver-mysql
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'mysql';
}
