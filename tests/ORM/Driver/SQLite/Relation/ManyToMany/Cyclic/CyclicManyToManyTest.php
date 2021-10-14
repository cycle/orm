<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLite\Relation\ManyToMany\Cyclic;

class CyclicManyToManyTest extends \Cycle\ORM\Tests\Relation\ManyToMany\Cyclic\CyclicManyToManyTest
{
    public const DRIVER = 'sqlite';
}
