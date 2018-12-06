<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\ScopedInterface;
use Spiral\ORM\Context\AcceptorInterface;
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
     * @param CarrierInterface $carrier
     * @param State            $from
     * @param string           $fromKey
     * @param null|State       $to
     * @param string           $toKey
     */
    protected function forwardContext(
        CarrierInterface $carrier,
        State $from,
        string $fromKey,
        // here
        State $to,
        string $toKey
    ) {
        $carrier->waitContext($toKey, $this->isRequired());

        // forward key from state to the command (on change)
        $to->pull($toKey, $carrier, $toKey);

        // link 2 keys and trigger cascade falling right now (if exists)
        $from->pull($fromKey, $to, $toKey, true);
       // dump($from);

        //dump($to);
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param ScopedInterface $carrier
     * @param State           $from
     * @param string          $fromKey
     * @param string          $toKey
     */
    protected function forwardScope(
        ScopedInterface $carrier,
        State $from,
        string $fromKey,
        // here
        string $toKey
    ) {
        $carrier->waitScope($toKey);
        $from->pull($fromKey, $carrier, $toKey, true, AcceptorInterface::SCOPE);
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