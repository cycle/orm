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
    protected function promiseContext(
        ContextualInterface $command,
        State $parent,
        $parentKey,
        ?State $current,
        $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (empty($current) || $current->getKey($localKey) != $value) {
                $command->setContext($localKey, $value);
            }
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (empty($value = $source->getKey($parentKey))) {
                // not ready
                return;
            }

            // todo: avoid un-necessary updates?
            $command->setContext($localKey, $value);
        });
    }

    protected function promiseWhere(
        ScopedInterface $command,
        State $parent,
        $parentKey,
        ?State $current,
        $localKey
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