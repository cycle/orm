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
                // no changes
                $command->setContext($localKey, $parent->getKey($parentKey));
            }
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (empty($value = $source->getKey($parentKey))) {
                // not ready
                return;
            }

            $command->setContext($localKey, $source->getKey($parentKey));
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
                // no changes
                $command->setWhere(
                    [$localKey => $parent->getKey($parentKey)] + $command->getWhere()
                );
            }
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (empty($value = $source->getKey($parentKey))) {
                // not ready
                return;
            }

            $command->setWhere(
                [$localKey => $source->getKey($parentKey)] + $command->getWhere()
            );
        });
    }
}