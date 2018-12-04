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
use Spiral\ORM\State;

/**
 * Provides the ability to set the promises for command context and scopes linked
 * to related entity state change.
 */
trait ContextTrait
{
    /**
     * Configure context parameter using value from parent entity. Created promise.
     *
     * @param ContextualInterface $command
     * @param State               $parent
     * @param string              $parentKey
     * @param null|State          $current
     * @param string              $localKey
     */
    protected function promiseContext(
        ContextualInterface $command,
        State $parent,
        string $parentKey,
        State $current,
        string $localKey
    ) {
        $command->waitContext($localKey, $this->isRequired());
        $current->forward($command, $localKey, $localKey);
        $parent->forward($current, $parentKey, $localKey, true);
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param ScopedInterface $command
     * @param State           $parent
     * @param string          $parentKey
     * @param null|State      $current
     * @param string          $localKey
     */
    protected function promiseScope(
        ScopedInterface $command,
        State $parent,
        string $parentKey,
        State $current,
        string $localKey
    ) {
        $command->waitScope($localKey, $this->isRequired());
        $parent->forward($command, $parentKey, "scope:" . $localKey, true);
    }

    /**
     * Fetch key from the state.
     *
     * @param State  $state
     * @param string $key
     * @return mixed|null
     */
    protected function fetchKey(?State $state, string $key)
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