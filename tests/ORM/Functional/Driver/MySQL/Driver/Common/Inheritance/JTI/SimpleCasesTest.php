<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SimpleCasesTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-mysql
 */
class SimpleCasesTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
