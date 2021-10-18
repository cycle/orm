<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\ParentClassRelationsTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ParentClassRelationsTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
