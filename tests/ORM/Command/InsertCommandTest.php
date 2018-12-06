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
use Spiral\Database\Query\InsertQuery;
use Spiral\ORM\Command\Database\Insert;

class InsertCommandTest extends TestCase
{
    public function testDatabase()
    {
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $this->assertSame($db, $cmd->getDatabase());
    }

    public function testIsEmpty()
    {
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $this->assertTrue($cmd->isReady());
    }

    public function testIsEmptyData()
    {
        $cmd = new Insert(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value']
        );

        $this->assertSame(['name' => 'value'], $cmd->getData());
    }

//
//    public function testExecute()
//    {
//        $cmd = new Insert(
//            $m = m::mock(DatabaseInterface::class),
//            'table',
//            ['key' => 'value']
//        );
//
//        $cmd->push('name', 'value');
//      //  $this->assertSame(null, $cmd->getInsertID());
//
//        $m->expects('insert')->with('table')->andReturn(
//            $i = m::mock(InsertQuery::class)
//        );
//
//        $i->expects('values')->with(['name' => 'value', 'key' => 'value'])->andReturnSelf();
//        $i->expects('run')->andReturn(1);
//
//        $cmd->execute();
//        $this->assertSame(1, $cmd->getInsertID());
//    }
}