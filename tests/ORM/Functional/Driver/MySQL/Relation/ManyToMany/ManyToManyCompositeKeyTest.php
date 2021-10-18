<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyCompositeKeyTest
{
    public const DRIVER = 'mysql';
}
