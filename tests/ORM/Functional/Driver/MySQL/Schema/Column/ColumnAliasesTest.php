<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\ColumnAliasesTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ColumnAliasesTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
