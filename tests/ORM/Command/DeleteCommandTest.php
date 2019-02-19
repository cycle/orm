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
use Cycle\ORM\Command\Database\Delete;

class DeleteCommandTest extends TestCase
{
    /**
     * @expectedException \Cycle\ORM\Exception\CommandException
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