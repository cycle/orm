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
use Spiral\ORM\Command\Database\Update;

class UpdateCommandTest extends TestCase
{
    public function testIsEmpty()
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            [],
            []
        );

        $this->assertTrue($cmd->isReady());
    }

    public function testIsEmptyData()
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value']
        );

        $this->assertSame(['name' => 'value'], $cmd->getData());
    }

    public function testIsEmptyContext()
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            ['where' => 'value']
        );

        $cmd->push('key', 'value');
        $this->assertSame(['key' => 'value'], $cmd->getContext());
    }

    public function testWhere()
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            []
        );

        $cmd->push('scope:key', 'value');
        $this->assertSame(['key' => 'value'], $cmd->getScope());
    }

    /**
     * @expectedException \Spiral\ORM\Exception\CommandException
     */
    public function testNoScope()
    {
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            ['name' => 'value'],
            []
        );

        $cmd->execute();
    }
}