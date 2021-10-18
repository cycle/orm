<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Benchmark;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Benchmark\BenchmarkDoubleLinkedTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class BenchmarkDoubleLinkedTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
