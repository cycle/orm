<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Classless;

/**
 * @group driver
 * @group driver-mysql
 */
class ClasslessCyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessCyclicReferencesTest
{
    public const DRIVER = 'mysql';
}
