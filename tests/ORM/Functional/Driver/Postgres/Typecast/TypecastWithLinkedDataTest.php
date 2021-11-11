<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\TypecastWithLinkedDataTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TypecastWithLinkedDataTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
