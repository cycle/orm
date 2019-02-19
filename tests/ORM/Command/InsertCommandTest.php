<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;
use Cycle\ORM\Command\Database\Insert;

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
}