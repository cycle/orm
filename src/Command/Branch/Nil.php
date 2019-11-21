<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

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
    /**
     * {@inheritdoc}
     */
    public function waitContext(string $key, bool $required = true): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     */
    public function isExecuted(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function complete(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        // nothing to do
    }
}
