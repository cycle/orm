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

trait PromiseTrait
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
        ?State $current,
        string $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (empty($current) || $current->getKey($localKey) != $value) {
                $command->setContext($localKey, $value);
            }
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (!empty($value = $source->getKey($parentKey))) {
                $command->setContext($localKey, $value);
            }
        });
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
        ?State $current,
        string $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (empty($current) || $current->getKey($localKey) != $value) {
                $command->setWhere($localKey, $value);
            }
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (!empty($value = $source->getKey($parentKey))) {
                $command->setWhere($localKey, $value);
            }
        });
    }
}