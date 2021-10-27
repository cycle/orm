<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\RepositoryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class RepositoryTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
