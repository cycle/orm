<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLite;

class ClasslessCyclicReferencesTest extends \Cycle\ORM\Tests\Classless\ClasslessCyclicReferencesTest
{
    public const DRIVER = 'sqlite';
}
