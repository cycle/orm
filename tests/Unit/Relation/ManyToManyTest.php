<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Relation;

use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\ManyToMany;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Cycle\Testing\Database\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ManyToManyTest
{
    public function emptyCollectionOfOneWayRelationIsResolvedAtOnce(): void
    {
        $orm = $this->orm();
        $tuple = $this->tuple();

        $this->relation($orm)->prepare(new Pool($orm), $tuple, []);

        Assert::count($tuple->state->getRelation('tags'), 0);
        Assert::same($tuple->state->getRelationStatus('tags'), RelationInterface::STATUS_RESOLVED);
    }

    public function emptyCollectionOfTwoWayRelationIsProcessed(): void
    {
        $orm = $this->orm();
        $tuple = $this->tuple();

        $this->relation($orm, [Relation::INVERSION => 'users'])->prepare(new Pool($orm), $tuple, []);

        Assert::same($tuple->state->getRelationStatus('tags'), RelationInterface::STATUS_PROCESS);
    }

    public function queueOfReferenceThatIsNotLoadedChangesNothing(): void
    {
        $orm = $this->orm();
        $tuple = $this->tuple();
        $reference = new Reference('tag', ['id' => 1]);
        $tuple->state->setRelation('tags', $reference);

        $this->relation($orm)->queue(new Pool($orm), $tuple);

        Assert::false($reference->hasValue());
        Assert::same($tuple->state->getRelationStatus('tags'), RelationInterface::STATUS_RESOLVED);
    }

    public function referenceWithoutScopeIsResolvedToEmptyStorage(): void
    {
        $reference = new Reference('tag', []);

        $result = $this->relation($this->orm())->resolve($reference, true);

        Assert::instanceOf($result, PivotedStorage::class);
        Assert::count($result, 0);
        Assert::same($reference->getValue(), $result);
    }

    public function referenceIsResolvedWithPivotsFromDatabase(): void
    {
        $orm = $this->orm();
        $db = $orm->getFactory()->database('default');
        $db->execute('CREATE TABLE tag (id INTEGER PRIMARY KEY, name TEXT)');
        $db->execute('CREATE TABLE tag_user_map (id INTEGER PRIMARY KEY, user_id INTEGER, tag_id INTEGER)');
        $db->execute("INSERT INTO tag (id, name) VALUES (1, 'php'), (2, 'go')");
        $db->execute('INSERT INTO tag_user_map (id, user_id, tag_id) VALUES (10, 1, 2)');
        $reference = new Reference('tag', ['id' => 1]);

        $result = $this->relation($orm)->resolve($reference, true);

        Assert::count($result, 1);
        $tag = $result->getElements()[0];
        Assert::same($tag->name, 'go');
        Assert::same($result->get($tag)->id, 10);
        Assert::same($reference->getValue(), $result);
    }

    public function initBuildsCollectionWithPivots(): void
    {
        $orm = $this->orm();
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');

        $result = $this->relation($orm)->init($orm->getService(EntityFactoryInterface::class), $node, [
            ['id' => 10, 'user_id' => 1, 'tag_id' => 2, '@' => ['id' => 2, 'name' => 'go']],
        ]);

        Assert::count($result, 1);
        $tag = $result[0];
        Assert::same($tag->name, 'go');
        $storage = $node->getRelation('tags');
        Assert::instanceOf($storage, PivotedStorage::class);
        Assert::same($storage->get($tag)->id, 10);
    }

    private function tuple(): Tuple
    {
        $node = new Node(Node::MANAGED, ['id' => 1], 'user');
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = $node;
        $tuple->state = $node->createState();
        return $tuple;
    }

    private function relation(ORM $orm, array $schema = []): ManyToMany
    {
        return new ManyToMany($orm, 'user', 'tags', 'tag', $schema + [
            Relation::CASCADE => true,
            Relation::NULLABLE => false,
            Relation::THROUGH_ENTITY => 'tag_context',
            Relation::INNER_KEY => 'id',
            Relation::OUTER_KEY => 'id',
            Relation::THROUGH_INNER_KEY => 'user_id',
            Relation::THROUGH_OUTER_KEY => 'tag_id',
        ]);
    }

    private function orm(): ORM
    {
        return new ORM(new Factory(Memory::manager()), new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id'],
            ],
            'tag' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name'],
            ],
            'tag_context' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'tag_id'],
            ],
        ]));
    }
}
