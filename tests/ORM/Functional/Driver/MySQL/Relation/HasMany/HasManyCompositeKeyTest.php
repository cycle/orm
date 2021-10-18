<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyCompositeKeyTest
{
    public const DRIVER = 'mysql';
}
