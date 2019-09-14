<?php


namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\ContextCarrierInterface;

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
    }

    public function getContext(): array
    {
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA)
    {
    }
}
