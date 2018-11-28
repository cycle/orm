<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Traits;

trait DestructTrait
{
    /** @var callable[] */
    private $onDestruct = [];

    /**
     * Handler to be invoked when command is being destructed.
     *
     * @param callable $closure
     */
    final public function onDestruct(callable $closure)
    {
        $this->onDestruct[] = $closure;
    }

    /**
     * Destructing the command and closing all the handlers.
     */
    public function __destruct()
    {
        foreach ($this->onDestruct as $closure) {
            call_user_func($closure, $this);
        }
    }
}