<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Benchmark;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Benchmark\BenchmarkDoubleLinkedTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class BenchmarkDoubleLinkedTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
