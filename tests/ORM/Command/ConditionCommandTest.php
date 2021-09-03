<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\CommandInterface;
use PHPUnit\Framework\TestCase;

class ConditionCommandTest extends TestCase
{
    private $testCommand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testCommand = new class() implements CommandInterface {
            private $executed = false;

            public function isReady(): bool
            {
                return true;
            }

            public function isExecuted(): bool
            {
                return $this->executed;
            }

            public function execute(): void
            {
                $this->executed = true;
            }

            public function complete(): void
            {
            }

            public function rollBack(): void
            {
            }
        };
    }

    public function testIterate(): void
    {
        $c = new Condition(
            $this->testCommand,
            function () {
                return true;
            }
        );

        foreach ($c as $n) {
            $this->assertInstanceOf(get_class($this->testCommand), $n);
        }
    }

    public function testIterateEmpty(): void
    {
        $c = new Condition(
            $this->testCommand,
            function () {
                return false;
            }
        );

        $this->assertCount(0, iterator_to_array($c));
    }

    public function testExecuted(): void
    {
        $c = new Condition(
            $this->testCommand,
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
