<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\TableRendererTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TableRendererTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
