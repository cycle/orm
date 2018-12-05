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
     * @param ContextualInterface $carrier
     * @param State               $source
     * @param string              $sourceKey
     * @param null|State          $target
     * @param string              $targetKey
     */
    protected function forwardContext(
        ContextualInterface $carrier,
        State $source,
        string $sourceKey,
        State $target,
        string $targetKey
    ) {
        $carrier->waitContext($targetKey, $this->isRequired());

        $target->forward($carrier, $targetKey, $targetKey);
        $source->forward($target, $sourceKey, $targetKey, true);
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param ScopedInterface $carrier
     * @param State           $source
     * @param string          $sourceKey
     * @param string          $targetKey
     */
    protected function forwardScope(ScopedInterface $carrier, State $source, string $sourceKey, string $targetKey)
    {
        $carrier->waitScope($targetKey, $this->isRequired());
        $source->forward($carrier, $sourceKey, "scope:" . $targetKey, true);
    }

    /**
     * Fetch key from the state.
     *
     * @param State $state
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