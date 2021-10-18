<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasOne;

/**
 * @group driver
 * @group driver-mysql
 */
class HasOneCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCompositeKeyTest
{
    public const DRIVER = 'mysql';
}
