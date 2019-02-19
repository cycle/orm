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
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Context\ConsumerInterface;

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

        $cmd->register('key', 'value', true);
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

        $cmd->register('key', 'value', false, ConsumerInterface::SCOPE);
        $this->assertSame(['key' => 'value'], $cmd->getScope());
    }

    /**
     * @expectedException \Cycle\ORM\Exception\CommandException
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