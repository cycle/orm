<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Command\Branch\Split;
use Spiral\Cycle\Command\ContextCarrierInterface;

class SplitCommandTest extends TestCase
{
    public function testNeverExecuted()
    {
        $command = new Split(new TestContextCommand(), new TestContextCommand());
        $this->assertFalse($command->isExecuted());
    }
}

class TestContextCommand implements ContextCarrierInterface
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

    public function waitContext(string $key, bool $required = true)
    {
        // TODO: Implement waitContext() method.
    }

    public function getContext(): array
    {
        // TODO: Implement getContext() method.
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA)
    {
        // TODO: Implement register() method.
    }
}