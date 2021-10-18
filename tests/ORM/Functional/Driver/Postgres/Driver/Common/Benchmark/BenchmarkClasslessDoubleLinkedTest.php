<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Benchmark;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Benchmark\BenchmarkClasslessDoubleLinkedTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class BenchmarkClasslessDoubleLinkedTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
