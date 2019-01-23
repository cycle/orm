<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation\Traits;

use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Command\ScopeCarrierInterface as CS;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Heap\Node;

/**
 * Provides the ability to set the promises for command context and scopes linked
 * to related entity state change.
 */
trait ContextTrait
{
    /**
     * Configure context parameter using value from parent entity. Created promise.
     *
     * @param Node      $from
     * @param string    $fromKey
     * @param CC        $carrier
     * @param null|Node $to
     * @param string    $toKey
     * @return CC
     */
    protected function forwardContext(Node $from, string $fromKey, CC $carrier, Node $to, string $toKey): CC
    {
        // do not execute until the key is given
        $carrier->waitContext($toKey, $this->isRequired());

        // forward key from state to the command (on change)
        $to->forward($toKey, $carrier, $this->columnName($to, $toKey));

        // link 2 keys and trigger cascade falling right now (if exists)
        $from->forward($fromKey, $to, $toKey, true);

        return $carrier;
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param Node   $from
     * @param string $fromKey
     * @param CS     $carrier
     * @param string $toKey
     * @return CS
     */
    protected function forwardScope(Node $from, string $fromKey, CS $carrier, string $toKey): CS
    {
        $column = $this->columnName($from, $toKey);

        $carrier->waitScope($column);
        $from->forward($fromKey, $carrier, $column, true, ConsumerInterface::SCOPE);

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
     * Return column name in database.
     *
     * @param Node   $node
     * @param string $field
     * @return string
     */
    abstract protected function columnName(Node $node, string $field): string;

    /**
     * True is given relation is required for the object to be saved (i.e. NOT NULL).
     *
     * @return bool
     */
    abstract public function isRequired(): bool;
}