<?php

declare(strict_types=1);

namespace Cycle\ORM\Exception;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Transaction\Tuple;

class TransactionException extends ORMException
{
    public static function unresolvedRelations(
        iterable $tuples,
        RelationProviderInterface $relProvider,
        ?\Throwable $e = null,
    ): self {
        $messages = [];
        foreach ($tuples as $tuple) {
            $role = $tuple->node->getRole();
            $map = $relProvider->getRelationMap($role);

            $status = $tuple->task === Tuple::TASK_STORE && $tuple->node->getStatus() === Node::NEW
                ? 'Create new'
                : match ($tuple->task) {
                    Tuple::TASK_STORE => 'Update',
                    default => 'Delete',
                };
            $message = "$status `$role`";
            foreach ($map->getMasters() as $name => $relation) {
                $relationStatus = $tuple->state->getRelationStatus($relation->getName());
                if ($relationStatus === RelationInterface::STATUS_RESOLVED) {
                    continue;
                }
                $message .= \sprintf("\n - %s (%s)", $name, $relation::class);
            }
            $messages[] = $message;
        }
        $messages = \array_unique($messages);
        $message = "Transaction can't be finished. Some relations can't be resolved:\n" . \implode("\n", $messages);

        return new self($message, 0, $e);
    }
}
