<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\STI;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\ManyToManyTest
{
    public const DRIVER = 'mysql';
}
