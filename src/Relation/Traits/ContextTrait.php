<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Command\ScopeCarrierInterface as CS;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Heap\Node;

/**
 * Provides the ability to set the promises for command context and scopes linked
 * to related entity state change.
 */
trait ContextTrait
{

    /**
     * True is given relation is required for the object to be saved (i.e. NOT NULL).
     *
     * @todo rename to isNullable and inverse the logic
     * @return bool
     */
    abstract public function isNotNullable(): bool;
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
        $toColumn = $this->columnName($to, $toKey);

        // do not execute until the key is given
        $carrier->waitContext($toColumn, $this->isNotNullable());

        // forward key from state to the command (on change)
        $to->forward($toKey, $carrier, $toColumn);

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
        if ($state === null) {
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
}
