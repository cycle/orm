<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\ContextCarrierInterface;

/**
 * Doing noting.
 *
 * @codeCoverageIgnore
 */
final class Nil implements ContextCarrierInterface
{
    public function waitContext(string $key, bool $required = true): void
    {
    }

    public function getContext(): array
    {
        return [];
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        // nothing to do
    }

    public function isExecuted(): bool
    {
        return false;
    }

    public function isReady(): bool
    {
        return true;
    }

    public function execute(): void
    {
        // nothing to do
    }

    public function complete(): void
    {
        // nothing to do
    }

    public function rollBack(): void
    {
        // nothing to do
    }
}
