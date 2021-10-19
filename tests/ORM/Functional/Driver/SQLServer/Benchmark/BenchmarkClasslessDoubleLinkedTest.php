<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Benchmark;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Benchmark\BenchmarkClasslessDoubleLinkedTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BenchmarkClasslessDoubleLinkedTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
