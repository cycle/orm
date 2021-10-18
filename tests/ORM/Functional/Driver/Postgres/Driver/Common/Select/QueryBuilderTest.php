<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\QueryBuilderTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class QueryBuilderTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
