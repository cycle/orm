<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Database\Insert;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;

class InsertCommandTest extends TestCase
{
    public function testDatabase(): void
    {
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $this->assertSame($db, $cmd->getDatabase());
    }

    public function testIsEmpty(): void
    {
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $this->assertTrue($cmd->isReady());
    }

    public function testIsEmptyData(): void
    {
        $cmd = new Insert(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value']
        );

        $this->assertSame(['name' => 'value'], $cmd->getData());
    }
}
