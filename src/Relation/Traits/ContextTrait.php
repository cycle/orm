<?php

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
     * True if given relation is not required for the object to be saved (i.e. NULL).
     */
    abstract public function isNullable(): bool;

    /**
     * Configure context parameter using value from parent entity. Created promise.
     */
    protected function forwardContext(Node $from, array $fromKeys, CC $carrier, Node $to, array $toKeys): CC
    {
        foreach ($fromKeys as $i => $fromKey) {
            $toKey = $toKeys[$i];

            // do not execute until the key is given
            // $carrier->waitContext($toKey, !$this->isNullable());
            $to->getState()->waitContext($toKey, !$this->isNullable());

            // echo __LINE__ . $to->getRole();
            // forward key from state to the command (on change)
            // $to->getState()->forward($toKey, $carrier, $toKey);

            // echo $from->getRole();
            // link 2 keys and trigger cascade falling right now (if exists)
            $from->getState()->forward($fromKey, $to->getState(), $toKey, true);

            // edge case while updating transitive key (exists in acceptor but does not exists in provider)
            // if (!array_key_exists($fromKey, $from->getInitialData())) {
            //     $carrier->waitContext($toKey, !$this->isNullable());
            // }
        }

        return $carrier;
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     */
    protected function forwardScope(Node $from, array $fromKeys, CS $carrier, array $toKeys): CS
    {
        foreach ($fromKeys as $i => $fromKey) {
            // $column = $this->columnName($from, $toKeys[$i]);
            $column = $toKeys[$i];

            $carrier->waitScope($column);
            $from->forward($fromKey, $carrier, $column, true, ConsumerInterface::SCOPE);
        }

        return $carrier;
    }

    /**
     * Fetch key from the state.
     */
    protected function fetchKey(Node $node, string $key)
    {
        if ($node === null) {
            return null;
        }

        return $node->getData()[$key] ?? null;
    }

    // /**
    //  * Return column name in database.
    //  */
    // abstract protected function columnName(Node $node, string $field): string;
}
