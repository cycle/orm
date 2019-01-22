<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command\Traits;

/**
 * Provides ability to carry context.
 */
trait ContextTrait
{
    /** @var array */
    protected $context = [];

    /** @var array */
    protected $waitContext = [];

    /**
     * Wait for the context value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitContext(string $key, bool $required = true)
    {
        if ($required) {
            $this->waitContext[$key] = null;
        }
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
    protected function setContext(string $name, $value)
    {
        $this->context[$name] = $value;
    }

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    protected function freeContext(string $key)
    {
        unset($this->waitContext[$key]);
    }
}