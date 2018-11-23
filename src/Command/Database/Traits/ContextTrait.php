<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Database\Traits;

/**
 * Provides ability to carry context.
 */
trait ContextTrait
{
    /** @var array */
    private $required = [];

    /** @var array */
    private $context = [];

    /**
     * Command is ready when no context is required.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return empty($this->required);
    }

    /**
     * Wait for the context value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitContext(string $key, bool $required = true)
    {
        $this->required[$key] = true;
        // we expect all values to be required here
    }

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    public function freeContext(string $key)
    {
        unset($this->required[$key]);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setContext(string $name, $value)
    {
        $this->context[$name] = $value;
    }
}