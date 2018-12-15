<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Command\Branch\Condition;
use Spiral\Cycle\Command\CommandInterface;

class ConditionCommandTest extends TestCase
{
    public function testIterate()
    {
        $c = new Condition(
            new TestCommand(),
            function () {
                return true;
            }
        );

        foreach ($c as $n) {
            $this->assertInstanceOf(TestCommand::class, $n);
        }
    }

    public function testIterateEmpty()
    {
        $c = new Condition(
            new TestCommand(),
            function () {
                return false;
            }
        );

        $this->assertCount(0, iterator_to_array($c));
    }

    public function testExecuted()
    {
        $c = new Condition(
            new TestCommand(),
            function () {
                return true;
            }
        );

        $n = iterator_to_array($c)[0];

        $this->assertFalse($c->isExecuted());
        $n->execute();
        $this->assertTrue($c->isExecuted());
    }
}


class TestCommand implements CommandInterface
{
    private $executed = false;

    public function isReady(): bool
    {
        return true;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function execute()
    {
        $this->executed = true;
    }

    public function complete()
    {
    }

    public function rollBack()
    {
    }

}