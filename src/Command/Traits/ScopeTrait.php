<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Traits;

trait ScopeTrait
{
    /** @var array */
    private $scope = [];

    /** @var array */
    private $waitScope = [];

    /**
     * Wait for the context value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitScope(string $key, bool $required = true)
    {
        $this->waitScope[$key] = true;
    }

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    public function freeScope(string $key)
    {
        unset($this->waitScope[$key]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setScope(string $key, $value)
    {
        $this->scope[$key] = $value;
    }

    /**
     * @return array
     */
    public function getScope(): array
    {
        return $this->scope;
    }
}