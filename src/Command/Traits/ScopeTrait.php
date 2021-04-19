<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command\Traits;

trait ScopeTrait
{
    /** @var array */
    protected $scope = [];

    /** @var array */
    protected $waitScope = [];

    /**
     * Wait for the context value.
     *
     * @param string ...$keys
     */
    public function waitScope(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->waitScope[$key] = true;
        }
    }

    /**
     * @return array
     */
    public function getScope(): array
    {
        return $this->scope;
    }

    /**
     * Set scope value.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function setScope(string $key, $value): void
    {
        $this->scope[$key] = $value;
    }

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    protected function freeScope(string $key): void
    {
        unset($this->waitScope[$key]);
    }
}
