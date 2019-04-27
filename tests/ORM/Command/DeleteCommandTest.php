<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Database\Delete;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\DatabaseInterface;

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