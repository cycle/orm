<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyCompositeKeyTest
{
    public const DRIVER = 'postgres';
}
