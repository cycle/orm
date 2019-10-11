<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Branch\Split;
use Cycle\ORM\Tests\Fixtures\TestContextCommand;
use PHPUnit\Framework\TestCase;

class SplitCommandTest extends TestCase
{
    public function testNeverExecuted(): void
    {
        $command = new Split(new TestContextCommand(), new TestContextCommand());
        $this->assertFalse($command->isExecuted());
    }
}
