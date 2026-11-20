<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation\SpecialValue;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Node::class)]
final class NodeTest
{
    public function syncStateReturnsOnlyChangedFields(): void
    {
        $node = new Node(Node::MANAGED, ['a' => 1, 'b' => 2], 'user');
        $state = $node->createState();
        $state->register('b', 3);
        $state->updateTransactionData();

        $changes = $node->syncState($this->relationMap(), $state);

        Assert::same($changes, ['b' => 3]);
        Assert::same($node->getData(), ['a' => 1, 'b' => 3]);
        Assert::same($node->getStatus(), Node::MANAGED);
        Assert::false($node->hasState());
    }

    public function syncStateSkipsUnsetRelationsAndKeepsTheRest(): void
    {
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');
        $state = $node->createState();
        $reference = new Reference('profile', ['id' => 1]);
        $state->setRelation('notLoaded', SpecialValue::notSet());
        $state->setRelation('profile', $reference);

        $changes = $node->syncState($this->relationMap(), $state);

        Assert::same($changes, ['profile' => $reference]);
        Assert::same($node->getRelation('profile'), $reference);
        Assert::false($node->hasRelation('notLoaded'));
    }

    private function relationMap(): RelationMap
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn([]);
        $schema->allows('define')->andReturn(null);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn(m::mock(FactoryInterface::class));
        return RelationMap::build($orm, 'user');
    }
}
