<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\EmptyReference;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\Morphed\RefersToMorphed;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Mockery\MockInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RefersToMorphedTest
{
    private EntityProviderInterface&MockInterface $provider;

    public function referenceOfNodeWithoutMorphKeyHasNoRole(): void
    {
        $reference = $this->relation()->initReference(new Node(Node::MANAGED, ['owner_id' => 1], 'image'));

        Assert::instanceOf($reference, EmptyReference::class);
        Assert::same($reference->getRole(), '?');
        Assert::null($reference->getValue());
    }

    public function referenceOfNodeWithMorphKeyTargetsItsRole(): void
    {
        $reference = $this->relation()->initReference(
            new Node(Node::MANAGED, ['owner_id' => 1, 'owner_type' => 'post'], 'image'),
        );

        Assert::instanceOf($reference, Reference::class);
        Assert::same($reference->getRole(), 'post');
        Assert::same($reference->getScope(), ['id' => 1]);
    }

    public function prepareOfReferenceTakesMorphKeyFromItsRole(): void
    {
        $tuple = $this->tuple();
        $relation = $this->relation();
        $this->provider->allows('get')->andReturn(null);

        $relation->prepare(new Pool(m::mock(ORMInterface::class)), $tuple, new Reference('post', ['id' => 7]));

        Assert::same($tuple->state->getData()['owner_type'], 'post');
    }

    public function prepareOfEmptyReferenceKeepsMorphKey(): void
    {
        $tuple = $this->tuple();
        $reference = new EmptyReference('post', null);

        $this->relation()->prepare(new Pool(m::mock(ORMInterface::class)), $tuple, $reference);

        Assert::same($tuple->state->getRelation('owner'), $reference);
        Assert::same($tuple->state->getData()['owner_type'], 'user');
    }

    private function tuple(): Tuple
    {
        $node = new Node(Node::MANAGED, ['id' => 1, 'owner_id' => 3, 'owner_type' => 'user'], 'image');
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    private function relation(): RefersToMorphed
    {
        $this->provider = m::mock(EntityProviderInterface::class);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn(m::mock(SchemaInterface::class));
        $orm->allows('getService')->with(MapperProviderInterface::class)->andReturn(m::mock(MapperProviderInterface::class));
        $orm->allows('getService')->with(EntityProviderInterface::class)->andReturn($this->provider);

        return new RefersToMorphed($orm, 'image', 'owner', 'owner', [
            Relation::INNER_KEY => 'owner_id',
            Relation::OUTER_KEY => 'id',
            Relation::MORPH_KEY => 'owner_type',
            Relation::CASCADE => true,
            Relation::NULLABLE => true,
        ]);
    }
}
