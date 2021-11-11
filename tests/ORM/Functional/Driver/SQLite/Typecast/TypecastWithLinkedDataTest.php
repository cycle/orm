<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastWithLinkedDataTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class TypecastWithLinkedDataTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
