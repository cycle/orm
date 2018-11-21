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
use Spiral\ORM\Command\Database\DeleteCommand;

class DeleteCommandTest extends TestCase
{
    /**
     * @expectedException \Spiral\ORM\Exception\CommandException
     */
    public function testNoScope()
    {
        $cmd = new DeleteCommand(
            m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $cmd->execute();
    }
}