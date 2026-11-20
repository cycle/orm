<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\JsonTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class JsonTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
