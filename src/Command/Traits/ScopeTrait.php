<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command\Traits;

trait ScopeTrait
{
    /** @var array */
    protected $scope = [];

    /** @var array */
    protected $waitScope = [];

    /**
     * Wait for the context value.
     *
     * @param string $key
     */
    public function waitScope(string $key)
    {
        $this->waitScope[$key] = true;
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
    protected function setScope(string $key, $value)
    {
        $this->scope[$key] = $value;
    }

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    protected function freeScope(string $key)
    {
        unset($this->waitScope[$key]);
    }
}