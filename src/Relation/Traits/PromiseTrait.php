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
use Spiral\ORM\StateInterface;

trait PromiseTrait
{
    /**
     * Configure context parameter using value from parent entity. Created promise.
     *
     * @param ContextualInterface $command
     * @param StateInterface      $parent
     * @param string              $parentKey
     * @param null|StateInterface $current
     * @param string              $localKey
     */
    protected function promiseContext(
        ContextualInterface $command,
        StateInterface $parent,
        string $parentKey,
        ?StateInterface $current,
        string $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (empty($current) || $current->getKey($localKey) != $value) {
                $command->setContext($localKey, $value);
            }
        }

        $parent->onChange(function (StateInterface $source) use ($command, $localKey, $parentKey) {
            if (!empty($value = $source->getKey($parentKey))) {
                $command->setContext($localKey, $value);
            }
        });
    }

    /**
     * Configure where parameter in scoped command based on key provided by the
     * parent entity. Creates promise.
     *
     * @param ScopedInterface     $command
     * @param StateInterface      $parent
     * @param string              $parentKey
     * @param null|StateInterface $current
     * @param string              $localKey
     */
    protected function promiseScope(
        ScopedInterface $command,
        StateInterface $parent,
        string $parentKey,
        ?StateInterface $current,
        string $localKey
    ) {
        if (!empty($value = $parent->getKey($parentKey))) {
            if (empty($current) || $current->getKey($localKey) != $value) {
                $command->setWhere($localKey, $value);
            }
        }

        $parent->onChange(function (StateInterface $source) use ($command, $localKey, $parentKey) {
            if (!empty($value = $source->getKey($parentKey))) {
                $command->setWhere($localKey, $value);
            }
        });
    }
}