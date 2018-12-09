<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Command\ScopeCarrierInterface;
use Spiral\ORM\Context\ConsumerInterface;
use Spiral\ORM\Node;

/**
 * Provides the ability to set the promises for command context and scopes linked
 * to related entity state change.
 */
trait ContextTrait
{
    /**
     * Configure context parameter using value from parent entity. Created promise.
     *
     * @param Node                    $from
     * @param string                  $fromKey
     * @param ContextCarrierInterface $carrier
     * @param null|Node               $to
     * @param string                  $toKey
     * @return ContextCarrierInterface
     */
    protected function forwardContext(
        Node $from,
        string $fromKey,
        ContextCarrierInterface $carrier,
        Node $to,
        string $toKey
    ): ContextCarrierInterface {
        $carrier->waitContext($toKey, $this->isRequired());

        // forward key from state to the command (on change)
        $to->listen($toKey, $carrier, $toKey);

        // link 2 keys and trigger cascade falling right now (if exists)
        $from->listen($fromKey, $to, $toKey, true);

        return $carrier;
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param Node                  $from
     * @param string                $fromKey
     * @param ScopeCarrierInterface $carrier
     * @param string                $toKey
     * @return ScopeCarrierInterface
     */
    protected function forwardScope(
        Node $from,
        string $fromKey,
        ScopeCarrierInterface $carrier,
        string $toKey
    ): ScopeCarrierInterface {
        $carrier->waitScope($toKey);
        $from->listen($fromKey, $carrier, $toKey, true, ConsumerInterface::SCOPE);

        return $carrier;
    }

    /**
     * Fetch key from the state.
     *
     * @param Node   $state
     * @param string $key
     * @return mixed|null
     */
    protected function fetchKey(?Node $state, string $key)
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