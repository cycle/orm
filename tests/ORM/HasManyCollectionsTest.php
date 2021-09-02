<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\CustomCollection;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class HasManyCollectionsTest extends BaseTest
{
    use TableTrait;

    /**
     * @var CollectionFactoryInterface[]
     */
    private array $collectionFactory = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->collectionFactory = [];

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'level'   => 'integer',
            'message' => 'string'
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 1, 'msg 1'],
                [1, 2, 'msg 2'],
                [1, 3, 'msg 3'],
                [1, 4, 'msg 4'],
                [2, 1, 'msg 2.1'],
                [2, 2, 'msg 2.2'],
                [2, 3, 'msg 2.3'],
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    /**
     * @dataProvider collectionTypesProvider
     */
    public function testCustomCollectionLoad(?string $factory, bool $lazy, string $class): void
    {
        $this->orm = $this->createOrm()->withSchema(
            new Schema($this->schemaWithCollectionType(User::class, 'comments', $factory))
        );

        $select = new Select($this->orm, User::class);
        if (!$lazy) {
            $select->load('comments');
        }
        $res = $select->wherePK(1)->fetchOne();

        $this->assertInstanceOf($class, $res->comments);
    }

    /**
     * @dataProvider collectionFactoryProvider
     */
    public function testCustomCollectionPersist(string $factory, string $type): void
    {
        $this->orm = $this->createOrm()->withSchema(
            new Schema($this->schemaWithCollectionType(User::class, 'comments', $factory))
        );
        $comments = $this->collectionFactory[$factory]->collect([
            $this->orm->make(Comment::class, ['level' => 90, 'message' => 'test 90']),
            $this->orm->make(Comment::class, ['level' => 91, 'message' => 'test 91']),
            $this->orm->make(Comment::class, ['level' => 92, 'message' => 'test 92']),
            $this->orm->make(Comment::class, ['level' => 93, 'message' => 'test 93']),
        ]);
        $user = $this->orm->make(User::class, ['email' => 'new-test@mail', 'balance' => 101]);
        $user->comments = $comments;

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(5);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);
    }

    public function collectionFactoryProvider(): array
    {
        return [
            'Doctrine collection factory' => ['doctrine', \Doctrine\Common\Collections\Collection::class],
            'Illuminate collection factory' => ['illuminate', \Illuminate\Support\Collection::class],
            'Array factory' => ['array', 'array'],
        ];
    }

    public function collectionTypesProvider(): array
    {
        return [
            'Default lazy' => [null, true, \Doctrine\Common\Collections\Collection::class],
            'Default eager' => [null, false, \Doctrine\Common\Collections\Collection::class],
            'Alias Common Doctrine collection lazy' => ['doctrine', true, \Doctrine\Common\Collections\Collection::class],
            'Alias Common Doctrine collection eager' => ['doctrine', false, \Doctrine\Common\Collections\Collection::class],
            'Alias Common Illuminate collection lazy' => ['illuminate', true, \Illuminate\Support\Collection::class],
            'Alias Common Illuminate collection eager' => ['illuminate', false, \Illuminate\Support\Collection::class],
            'Class Custom Doctrine collection lazy' => [CustomCollection::class, true, CustomCollection::class],
            'Class Custom Doctrine collection eager' => [CustomCollection::class, false, CustomCollection::class],
        ];
    }

    private function createOrm(): ORMInterface
    {
        $this->collectionFactory['doctrine'] = new DoctrineCollectionFactory();
        $this->collectionFactory['illuminate'] = new IlluminateCollectionFactory();
        $this->collectionFactory['array'] = new ArrayCollectionFactory();

        return new ORM(
            (new Factory(
                $this->dbal,
                RelationConfig::getDefault(),
                null,
                $this->collectionFactory['doctrine']
            ))
                ->withCollectionFactory(
                    'doctrine',
                    $this->collectionFactory['doctrine'],
                    \Doctrine\Common\Collections\Collection::class
                )
                ->withCollectionFactory(
                    'illuminate',
                    $this->collectionFactory['illuminate'],
                    \Illuminate\Support\Collection::class
                )
                ->withCollectionFactory(
                    'array',
                    $this->collectionFactory['array'],
                    \Illuminate\Support\Collection::class
                ),
            new Schema([])
        );
    }

    private function schemaWithCollectionType(
        string $role,
        string $relation,
        ?string $collection,
        array $schema = null
    ): array {
        $schema ??= $this->getSchemaArray();
        $schema[$role][Schema::RELATIONS][$relation][Relation::COLLECTION_TYPE] = $collection;
        return $schema;
    }

    private function getSchemaArray(): array
    {
        return [
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Comment::class => [
                Schema::ROLE        => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'level', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ];
    }
}
