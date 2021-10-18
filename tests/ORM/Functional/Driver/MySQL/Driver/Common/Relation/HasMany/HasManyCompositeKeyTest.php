<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyCompositeKeyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-mysql
 */
class HasManyCompositeKeyTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
