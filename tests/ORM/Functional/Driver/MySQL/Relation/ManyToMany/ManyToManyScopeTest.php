<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyScopeTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyScopeTest
{
    public const DRIVER = 'mysql';
}
