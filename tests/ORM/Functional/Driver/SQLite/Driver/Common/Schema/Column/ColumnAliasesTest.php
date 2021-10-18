<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\ColumnAliasesTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class ColumnAliasesTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
