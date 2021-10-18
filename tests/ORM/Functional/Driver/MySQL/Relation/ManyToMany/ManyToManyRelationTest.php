<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-mysql
 */
class ManyToManyRelationTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyRelationTest
{
    public const DRIVER = 'mysql';
}
