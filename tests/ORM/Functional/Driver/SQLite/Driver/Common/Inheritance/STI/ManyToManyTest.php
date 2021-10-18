<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\ManyToManyTest
{
    public const DRIVER = 'sqlite';
}
