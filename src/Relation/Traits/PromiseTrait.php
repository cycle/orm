<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\ScopedInterface;
use Spiral\ORM\StateInterface;

trait PromiseTrait
{
    /**
     * Configure context parameter using value from parent entity. Created promise.
     *
     * @param ContextualInterface $command
     * @param StateInterface      $parent
     * @param string              $parentKey
     * @param null|StateInterface $current
     * @param string              $localKey
     */
    protected function promiseContext(
        ContextualInterface $command,
        StateInterface $parent,
        string $parentKey,
        ?StateInterface $current,
        string $localKey
    ) {
        $handler = function (StateInterface $state) use ($command, $localKey, $parentKey, $current) {
            if (!empty($value = $this->fetchKey($state, $parentKey))) {
                if ($this->fetchKey($current, $localKey) != $value) {
                    $command->setContext($localKey, $value);
                }

                $command->freeContext($localKey);
            }
        };

        $command->waitContext($localKey, $this->isRequired());

        call_user_func($handler, $parent);
        $parent->onChange($handler);
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param ScopedInterface     $command
     * @param StateInterface      $parent
     * @param string              $parentKey
     * @param null|StateInterface $current
     * @param string              $localKey
     */
    protected function promiseScope(
        ScopedInterface $command,
        StateInterface $parent,
        string $parentKey,
        ?StateInterface $current,
        string $localKey
    ) {
        $handler = function (StateInterface $state) use ($command, $localKey, $parentKey, $current) {
            if (!empty($value = $this->fetchKey($state, $parentKey))) {
                if ($this->fetchKey($current, $localKey) != $value) {
                    $command->setScope($localKey, $value);
                }

                $command->freeScope($localKey);
            }
        };

        $command->waitScope($localKey, $this->isRequired());
        call_user_func($handler, $parent);
        $parent->onChange($handler);
    }

    /**
     * Fetch key from the state.
     *
     * @param StateInterface $state
     * @param string         $key
     * @return mixed|null
     */
    protected function fetchKey(?StateInterface $state, string $key)
    {
        if (is_null($state)) {
            return null;
        }

        return $state->getData()[$key] ?? null;
    }

    /**
     * True is given relation is required for the object to be saved (i.e. NOT NULL).
     *
     * @return bool
     */
    abstract public function isRequired(): bool;
}