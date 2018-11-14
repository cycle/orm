<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Query\UpdateQuery;
use Spiral\ORM\Command\Database\UpdateCommand;

class UpdateCommandTest extends TestCase
{
    public function testIsEmpty()
    {
        $cmd = new UpdateCommand(
            m::mock(DatabaseInterface::class),
            'table',
            [],
            [],
            null
        );

        $this->assertTrue($cmd->isEmpty());
    }

    public function testIsEmptyData()
    {
        $cmd = new UpdateCommand(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value'],
            1
        );

        $this->assertFalse($cmd->isEmpty());
        $this->assertSame(['name' => 'value'], $cmd->getData());
    }

    public function testIsEmptyPK()
    {
        $cmd = new UpdateCommand(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value'],
            null
        );

        $this->assertTrue($cmd->isEmpty());
    }

    public function testIsEmptyContext()
    {
        $cmd = new UpdateCommand(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value'],
            1
        );

        $this->assertFalse($cmd->isEmpty());

        $cmd->setContext('key', 'value');
        $this->assertSame(['key' => 'value'], $cmd->getContext());
    }

    public function testExecute()
    {
        $cmd = new UpdateCommand(
            $m = m::mock(DatabaseInterface::class),
            'table',
            ['key' => 'value'],
            ['where' => 'value']
        );

        $cmd->setContext('name', 'value');
        $this->assertSame(null, $cmd->getPrimaryKey());

        $m->expects('update')->with('table',
            ['name' => 'value', 'key' => 'value'],
            ['where' => 'value']
        )->andReturn(
            $i = m::mock(UpdateQuery::class)
        );

        $i->expects('run');

        $cmd->execute();
    }
}