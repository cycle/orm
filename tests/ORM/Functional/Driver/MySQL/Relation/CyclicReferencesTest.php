<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\CyclicReferencesTest
{
    public const DRIVER = 'mysql';
}
