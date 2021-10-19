<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyRelationTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
