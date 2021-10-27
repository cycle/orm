<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\ColumnAliasesTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ColumnAliasesTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
