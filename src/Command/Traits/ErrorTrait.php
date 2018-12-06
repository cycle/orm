<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Traits;

// i miss you go
trait ErrorTrait
{
    public function __toString()
    {
        $missing = [];
        if (property_exists($this, 'waitScope')) {
            foreach ($this->waitScope ?? [] as $name => $n) {
                $missing[] = "scope:{$name}";
            }
        }

        if (property_exists($this, 'waitContext')) {
            foreach ($this->waitContext ?? [] as $name => $n) {
                $missing[] = "{$name}";
            }
        }

        return sprintf("%s(%s)", get_class($this), join(", ", $missing));
    }
}