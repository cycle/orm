<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Integration\Case2;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case2\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
