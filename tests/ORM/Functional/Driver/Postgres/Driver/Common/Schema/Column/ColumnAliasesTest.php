<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\ColumnAliasesTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ColumnAliasesTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
