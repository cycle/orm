<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Treap\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Query\InsertQuery;
use Spiral\Treap\Command\Database\InsertCommand;

class InsertCommandTest extends TestCase
{
    public function testIsEmpty()
    {
        $insert = new InsertCommand(
            m::mock(DatabaseInterface::class),
            'table', []
        );

        $this->assertTrue($insert->isEmpty());
    }

    public function testIsEmptyData()
    {
        $insert = new InsertCommand(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value']
        );

        $this->assertFalse($insert->isEmpty());
        $this->assertSame(['name' => 'value'], $insert->getData());
    }

    public function testIsEmptyContext()
    {
        $insert = new InsertCommand(
            m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $this->assertTrue($insert->isEmpty());

        $insert->addContext('name', 'value');
        $this->assertFalse($insert->isEmpty());
    }

    public function testExecute()
    {
        $insert = new InsertCommand(
            $m = m::mock(DatabaseInterface::class),
            'table',
            ['key' => 'value']
        );

        $insert->addContext('name', 'value');
        $this->assertSame(null, $insert->getPrimaryKey());

        $m->expects('insert')->with('table')->andReturn(
            $i = m::mock(InsertQuery::class)
        );

        $i->expects('values')->with(['name' => 'value', 'key' => 'value'])->andReturnSelf();
        $i->expects('run')->andReturn(1);

        $insert->execute();

        $this->assertSame(1, $insert->getPrimaryKey());
    }
}