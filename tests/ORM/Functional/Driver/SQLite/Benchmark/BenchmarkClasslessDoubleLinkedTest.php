<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Benchmark;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Benchmark\BenchmarkClasslessDoubleLinkedTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class BenchmarkClasslessDoubleLinkedTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
