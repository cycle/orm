<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Exception;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(TransactionException::class)]
final class TransactionExceptionTest
{
    public function listsUnresolvedMastersOfEveryTuple(): void
    {
        [$provider, $class] = $this->relationProvider(['profile', 'group', 'avatar']);
        $previous = new \RuntimeException('cause');

        $e = TransactionException::unresolvedRelations([
            $this->tuple(Tuple::TASK_STORE, Node::NEW, ['group' => RelationInterface::STATUS_RESOLVED]),
            $this->tuple(Tuple::TASK_STORE, Node::MANAGED, ['profile' => RelationInterface::STATUS_RESOLVED]),
            $this->tuple(Tuple::TASK_STORE, Node::MANAGED, ['profile' => RelationInterface::STATUS_RESOLVED]),
            $this->tuple(Tuple::TASK_DELETE, Node::NEW, [
                'profile' => RelationInterface::STATUS_RESOLVED,
                'group' => RelationInterface::STATUS_RESOLVED,
                'avatar' => RelationInterface::STATUS_RESOLVED,
            ]),
        ], $provider, $previous);

        Assert::same($e->getMessage(), \implode("\n", [
            "Transaction can't be finished. Some relations can't be resolved:",
            'Create new `user`',
            " - profile ($class)",
            " - avatar ($class)",
            'Update `user`',
            " - group ($class)",
            " - avatar ($class)",
            'Delete `user`',
        ]));
        Assert::same($e->getCode(), 0);
        Assert::same($e->getPrevious(), $previous);
    }

    /**
     * @param array<string, int> $relationStatuses
     */
    private function tuple(int $task, int $nodeStatus, array $relationStatuses): Tuple
    {
        $tuple = new Tuple($task, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = new Node($nodeStatus, [], 'user');
        $tuple->state = new State($nodeStatus, []);
        foreach ($relationStatuses as $name => $status) {
            $tuple->state->setRelationStatus($name, $status);
        }
        return $tuple;
    }

    /**
     * @param list<non-empty-string> $names Master relations of the `user` role.
     *
     * @return array{RelationProviderInterface, class-string} The provider and the class of every relation.
     */
    private function relationProvider(array $names): array
    {
        $relations = [];
        foreach ($names as $name) {
            $relations[$name] = [
                Relation::TYPE => Relation::BELONGS_TO,
                Relation::TARGET => $name,
                Relation::SCHEMA => [Relation::INNER_KEY => "{$name}_id", Relation::OUTER_KEY => 'id'],
            ];
        }
        $schema = new Schema(['user' => [SchemaInterface::RELATIONS => $relations]]);
        $factory = m::mock(FactoryInterface::class);
        $class = null;
        foreach ($names as $name) {
            $relation = m::mock(DependencyInterface::class);
            $relation->allows('getName')->andReturn($name);
            $class = $relation::class;
            $factory->allows('relation')->with(m::any(), m::any(), 'user', $name)->andReturn($relation);
        }
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getFactory')->andReturn($factory);
        $orm->allows('getSchema')->andReturn($schema);
        $map = RelationMap::build($orm, 'user');

        $provider = m::mock(RelationProviderInterface::class);
        $provider->allows('getRelationMap')->with('user')->andReturn($map);
        return [$provider, $class];
    }
}
