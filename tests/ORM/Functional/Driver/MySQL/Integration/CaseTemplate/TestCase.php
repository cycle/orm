<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Integration\CaseTemplate;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\TestCase as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class TestCase extends CommonClass
{
    public const DRIVER = 'mysql';
}
