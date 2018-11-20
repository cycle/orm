<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Spiral\ORM\Command\ContextualCommandInterface;
use Spiral\ORM\State;

trait PromiseTrait
{
    protected function promiseContext(
        ContextualCommandInterface $command,
        State $parent,
        $parentKey,
        ?State $current,
        $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (!empty($current) && $current->getKey($localKey) == $value) {
                // no changes
                return;
            }

            $command->setContext($localKey, $parent->getKey($parentKey));
            return;
        }

        $parent->onUpdate(function (State $source) use ($command, $localKey, $parentKey) {
            if (empty($value = $source->getKey($parentKey))) {
                // not ready
                return;
            }

            $command->setContext($localKey, $source->getKey($parentKey));
        });
    }
}