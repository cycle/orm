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
     * @return array
     */
    public function getScope(): array
    {
        return $this->scope;
    }
}