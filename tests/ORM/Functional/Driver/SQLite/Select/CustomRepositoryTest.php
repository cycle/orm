<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

/**
 * @group driver
 * @group driver-sqlite
 */
class CustomRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\CustomRepositoryTest
{
    public const DRIVER = 'sqlite';
}
