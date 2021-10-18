<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Select;

/**
 * @group driver
 * @group driver-mysql
 */
class CustomRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\CustomRepositoryTest
{
    public const DRIVER = 'mysql';
}
