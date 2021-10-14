<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLite\Relation\HasMany\Cyclic;

class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'sqlite';
}
