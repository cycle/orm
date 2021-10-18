<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyLoadingTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyLoadingTest
{
    public const DRIVER = 'mysql';
}
