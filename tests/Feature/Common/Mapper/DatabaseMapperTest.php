<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Mapper;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\ClasslessMapper;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class DatabaseMapperTest extends OrmTestCase
{
    public function castKeepsRelationValuesThatAreNotRawData(): void
    {
        $posts = new \ArrayObject();

        $data = $this->orm->getMapper('user')->cast(['id' => 1, 'posts' => $posts]);

        Assert::same($data['posts'], $posts);
    }

    public function queueCreateSchedulesInsert(): void
    {
        $state = new State(Node::NEW, ['email' => 'foo@bar']);

        $this->orm->getMapper('user')->queueCreate(new \stdClass(), new Node(Node::NEW, [], 'user'), $state);

        Assert::same($state->getStatus(), Node::SCHEDULED_INSERT);
    }

    public function queueDeleteSchedulesDeleteByPrimaryKey(): void
    {
        $state = new State(Node::MANAGED, ['id' => 1]);

        $delete = $this->orm->getMapper('user')->queueDelete(
            new \stdClass(),
            new Node(Node::MANAGED, ['id' => 1], 'user'),
            $state,
        );

        Assert::same($state->getStatus(), Node::SCHEDULED_DELETE);
        Assert::true($delete->isScopeReady());
        Assert::same($delete->getScope(), ['id' => 1]);
    }

    public function queueDeleteWaitsForUnknownPrimaryKey(): void
    {
        $delete = $this->orm->getMapper('user')->queueDelete(
            new \stdClass(),
            new Node(Node::NEW, ['id' => null], 'user'),
            new State(Node::NEW, []),
        );

        Assert::false($delete->isScopeReady());
    }

    public function compositePrimaryKeyIsGeneratedOnce(): void
    {
        $pair = $this->orm->make('pair', ['title' => 'first']);

        $this->save($pair);

        Assert::same([$pair->a, $pair->b], [1, 1]);
        Assert::equals(
            $this->getDatabase()->select('a', 'b')->from('pair')->fetchAll(),
            [['a' => 1, 'b' => 1]],
        );
    }

    public function stdMapperSplitsFieldsAndRelations(): void
    {
        $mapper = $this->orm->getMapper('user');
        $user = $this->orm->make('user', ['email' => 'foo@bar']);
        $user->posts = [];
        $user->extra = 'not mapped';

        Assert::same($mapper->fetchFields($user), ['email' => 'foo@bar']);
        Assert::same(\array_keys($mapper->fetchRelations($user)), ['posts']);
    }

    public function classlessEntityHasPropertiesNamedAfterFields(): void
    {
        $entity = $this->orm->make('classless', ['email' => 'foo@bar']);

        Assert::true(\property_exists($entity, 'email'));
        Assert::false(\property_exists($entity, 'email_address'));
    }

    public function classlessMapperFetchesOnlyColumnFields(): void
    {
        $entity = $this->orm->make('classless', ['email' => 'foo@bar']);
        @$entity->extra = 'not mapped';

        Assert::same($this->orm->getMapper('classless')->fetchFields($entity), ['id' => null, 'email' => 'foo@bar']);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('post', ['id' => 'primary', 'user_id' => 'int', 'title' => 'string']);
        $this->makeTable('pair', ['a' => 'int', 'b' => 'int', 'title' => 'string'], pk: ['a', 'b']);
        $this->makeTable('classless', ['id' => 'primary', 'email_address' => 'string']);

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'posts' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => 'post',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            'post' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'title'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            'pair' => [
                Schema::ENTITY => SequencedPair::class,
                Schema::MAPPER => SequencedPairMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'pair',
                Schema::PRIMARY_KEY => ['a', 'b'],
                Schema::COLUMNS => ['a', 'b', 'title'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            'classless' => [
                Schema::MAPPER => ClasslessMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'classless',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id', 'email' => 'email_address'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}

class SequencedPair
{
    public $a;
    public $b;
    public $title;
}

/**
 * Hands out one number per call and uses it for both key parts.
 */
final class SequencedPairMapper extends Mapper
{
    private int $sequence = 0;

    protected function nextPrimaryKey(): ?array
    {
        ++$this->sequence;
        return ['a' => $this->sequence, 'b' => $this->sequence];
    }
}
