<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\JsonTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
final class JsonTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
