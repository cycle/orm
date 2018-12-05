<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Traits;

/**
 * Provides ability to carry context.
 */
trait ContextTrait
{
    /** @var array */
    private $context = [];

    /** @var array */
    private $waitContext = [];

    /**
     * Wait for the context value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitContext(string $key, bool $required = true)
    {
        $this->waitContext[$key] = true;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setContext(string $name, $value)
    {
        $this->context[$name] = $value;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}