<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;

interface RelationInterface
{
    public function isCascade(): bool;

    public function init($data): array;

    public function initPromise(State $state, $data): ?PromiseInterface;

    public function extract($value);

    public function queueRelation(
        ContextualInterface $command,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface;
}