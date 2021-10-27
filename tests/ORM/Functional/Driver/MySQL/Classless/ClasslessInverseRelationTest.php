<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessInverseRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessInverseRelationTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
