<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;
use Spiral\Cycle\Command\Database\Delete;

class DeleteCommandTest extends TestCase
{
    /**
     * @expectedException \Spiral\Cycle\Exception\CommandException
     */
    public function testNoScope()
    {
        $cmd = new Delete(
            m::mock(DatabaseInterface::class),
            'table',
            []
        );

        $cmd->execute();
    }
}