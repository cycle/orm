<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Exception\CommandException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;

class DeleteCommandTest extends TestCase
{
    public function testNoScope(): void
    {
        $this->expectException(CommandException::class);

        $cmd = new Delete(
            m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $cmd->execute();
    }
}
