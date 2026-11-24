<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Exception\Relation\BadRelationValueException;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORM;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasMany;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class HasManyTest
{
    public function nonIterableValueIsRejected(): never
    {
        $orm = $this->orm();

        Expect::exception(BadRelationValueException::class)
            ->withMessage('Value for Has Many relation must be of the iterable type, int given.');

        $this->relation($orm)->prepare(new Pool($orm), $this->tuple(), 42);
    }

    public function onlyIterableDataIsCollected(): never
    {
        $relation = $this->relation($this->orm());

        Assert::same($relation->collect(['post']), ['post']);

        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('Collected data in the HasMany relation should be iterable.');

        $relation->collect('post');
    }

    public function referenceWithoutScopeIsResolvedToEmptyCollection(): void
    {
        $reference = new Reference('post', []);

        $result = $this->relation($this->orm())->resolve($reference, true);

        Assert::same($result, []);
        Assert::same($reference->getValue(), []);
    }

    public function queueOfReferenceThatIsNotLoadedChangesNothing(): void
    {
        $orm = $this->orm();
        $tuple = $this->tuple();
        $reference = new Reference('post', ['user_id' => 1]);
        $tuple->state->setRelation('posts', $reference);

        $this->relation($orm)->queue(new Pool($orm), $tuple);

        Assert::false($reference->hasValue());
        Assert::same($tuple->state->getRelationStatus('posts'), RelationInterface::STATUS_RESOLVED);
    }

    private function tuple(): Tuple
    {
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    private function relation(ORM $orm): HasMany
    {
        return new HasMany($orm, 'user', 'posts', 'post', [
            Relation::CASCADE => true,
            Relation::NULLABLE => false,
            Relation::INNER_KEY => 'id',
            Relation::OUTER_KEY => 'user_id',
        ]);
    }

    private function orm(): ORM
    {
        return new ORM(new Factory(Memory::manager()), new Schema([
            'user' => [
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id'],
            ],
            'post' => [
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id'],
            ],
        ]));
    }
}
