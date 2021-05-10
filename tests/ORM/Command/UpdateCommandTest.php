<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Exception\CommandException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;

class UpdateCommandTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            [],
            []
        );

        $this->assertTrue($cmd->isReady());
    }

    public function testIsEmptyData(): void
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value']
        );

        $this->assertSame(['name' => 'value'], $cmd->getData());
    }

    public function testIsEmptyContext(): void
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value']
        );

        $cmd->register('key', 'value', true);
        $this->assertSame(['key' => 'value'], $cmd->getContext());
    }

    public function testWhere(): void
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            []
        );

        $cmd->register('key', 'value', false, ConsumerInterface::SCOPE);
        $this->assertSame(['key' => 'value'], $cmd->getScope());
    }

    public function testNoScope(): void
    {
        $this->expectException(CommandException::class);

        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            []
        );

        $cmd->execute();
    }
}
