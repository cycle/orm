<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

/**
 * Provides ability to carry context.
 */
trait ContextTrait
{
    protected array $context = [];

    protected array $waitContext = [];

    /**
     * Wait for the context value.
     */
    public function waitContext(string $key, bool $required = true): void
    {
        if ($required) {
            $this->waitContext[$key] = null;
        }
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param mixed $value
     */
    public function setContext(string $name, $value): void
    {
        $this->context[$name] = $value;
    }

    /**
     * Indicate that context value is not required anymore.
     */
    public function freeContext(string $key): void
    {
        unset($this->waitContext[$key]);
    }
}
