<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany;

use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\ManyToMany;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany\Fixture\HiddenTagScope;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class ManyToManyClasslessTest extends OrmTestCase
{
    public function lazyTagsAreScopedTypecastAndCarryEagerRelations(): void
    {
        $user = $this->orm->get('user', ['id' => 1]);

        $tags = [...$user->tags->fetch()];

        Assert::same(\array_map(static fn(object $tag): string => $tag->name, $tags), ['tag a']);
        Assert::same($tags[0]->level, 5);
        Assert::same($tags[0]->category->title, 'common');
    }

    public function lazyTagsReuseEntitiesFromHeap(): void
    {
        $tag = $this->orm->get('tag', ['id' => 3]);
        $user = $this->orm->get('user', ['id' => 2]);

        Assert::same([...$user->tags->fetch()], [$tag]);
    }

    public function loadedTagsReuseEntitiesFromHeap(): void
    {
        $tag = $this->orm->get('tag', ['id' => 3]);

        $user = (new Select($this->orm, 'user'))->load('tags')->wherePK(2)->fetchOne();

        Assert::same([...$user->tags], [$tag]);
    }

    public function resolveWithoutLoadingKeepsReferenceEmpty(): void
    {
        $reference = new Reference('tag', ['id' => 1]);

        $result = $this->tags()->resolve($reference, false);

        Assert::null($result);
        Assert::false($reference->hasValue());
    }

    public function castOfEmptyDataIsEmptyArray(): void
    {
        Assert::same($this->tags()->cast(null), []);
        Assert::same($this->tags()->cast([]), []);
    }

    public function castUsesRoleOfEachRow(): void
    {
        $rows = [
            ['id' => '1', 'user_id' => '1', 'tag_id' => '1', '@' => ['id' => '1', 'level' => '5']],
            [
                LoaderInterface::ROLE_KEY => 'category',
                'id' => '2',
                'user_id' => '1',
                '@' => [LoaderInterface::ROLE_KEY => 'category', 'id' => '2', 'level' => '7'],
            ],
        ];

        $result = $this->tags()->cast($rows);

        Assert::same($result[0]['user_id'], 1);
        Assert::same($result[0]['@']['level'], 5);
        Assert::same($result[1]['user_id'], '1');
        Assert::same($result[1]['@']['level'], '7');
    }

    public function extractAcceptsTraversable(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();

        $storage = $this->tags()->extract((static function () use ($a, $b): \Generator {
            yield $a;
            yield $b;
        })());

        Assert::same($storage->getElements(), [$a, $b]);
    }

    public function extractRelatedTakesMissingPivotsFromOriginal(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $pivotA = new \stdClass();
        $pivotB = new \stdClass();
        $context = new \SplObjectStorage();
        $context[$a] = $pivotA;
        $originalContext = new \SplObjectStorage();
        $originalContext[$a] = new \stdClass();
        $originalContext[$b] = $pivotB;

        $related = $this->tags()->extractRelated(
            new PivotedStorage([$a, $b], $context),
            new PivotedStorage([$a, $b], $originalContext),
        );

        Assert::same($related->get($a), $pivotA);
        Assert::same($related->get($b), $pivotB);
    }

    public function linkingEntityOfAnotherRoleFails(): never
    {
        $user = $this->orm->make('user', ['email' => 'new@world.com']);
        $user->tags = [$this->orm->get('category', ['id' => 1])];

        Expect::exception(RelationException::class);

        $this->save($user);
    }

    public function newTagSharedByNewUsersIsLinkedToBoth(): void
    {
        $tag = $this->orm->make('tag', ['name' => 'shared', 'level' => 1]);
        $first = $this->orm->make('user', ['email' => 'first@world.com']);
        $second = $this->orm->make('user', ['email' => 'second@world.com']);
        $first->tags = [$tag];
        $second->tags = [$tag];

        $this->save($first, $second);

        $links = $this->getDatabase()->table('tag_user_map')->select('user_id')
            ->where('tag_id', $tag->id)->orderBy('user_id')->fetchAll();
        Assert::same(\array_map(static fn(array $row): int => (int) $row['user_id'], $links), [$first->id, $second->id]);
    }

    public function replacingUnresolvedTagsUnlinksOldOnes(): void
    {
        $user = $this->orm->get('user', ['id' => 1]);
        $user->tags = [$this->orm->get('tag', ['id' => 3])];

        $this->save($user);

        // Tag 2 is hidden by the target scope, so the relation never sees its link and keeps it.
        Assert::same($this->linkedTags(1), [2, 3]);
        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);
    }

    public function assigningUnresolvedTagsOfAnotherUserLinksThem(): void
    {
        $first = $this->orm->get('user', ['id' => 1]);
        $second = $this->orm->get('user', ['id' => 2]);
        $first->tags = $second->tags;

        $this->save($first);

        Assert::same($this->linkedTags(1), [2, 3]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('category', ['id' => 'primary', 'title' => 'string']);
        $this->makeTable('tag', [
            'id' => 'primary',
            'name' => 'string',
            'level' => 'string',
            'category_id' => 'integer,nullable',
        ]);
        $this->makeTable('tag_user_map', [
            'id' => 'primary',
            'user_id' => 'integer',
            'tag_id' => 'integer',
            'as' => 'string,nullable',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(['email'], [['hello@world.com'], ['another@world.com']]);
        $this->getDatabase()->table('category')->insertMultiple(['title'], [['common']]);
        $this->getDatabase()->table('tag')->insertMultiple(
            ['name', 'level', 'category_id'],
            [['tag a', '5', 1], ['hidden', '7', 1], ['tag c', '9', null]],
        );
        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id', 'as'],
            [[1, 1, 'primary'], [1, 2, 'secondary'], [2, 3, 'primary']],
        );

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'tags' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'tag',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'tag_context',
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'tag_id',
                        ],
                    ],
                ],
            ],
            'tag' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name', 'level', 'category_id'],
                Schema::TYPECAST => ['id' => 'int', 'level' => 'int', 'category_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::SCOPE => HiddenTagScope::class,
                Schema::RELATIONS => [
                    'category' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => 'category',
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'category_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            'category' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'category',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'title'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            'tag_context' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    private function tags(): ManyToMany
    {
        return $this->orm->getRelationMap('user')->getRelations()['tags'];
    }

    /**
     * @return list<int> IDs of the tags linked to the user, read from the pivot table
     */
    private function linkedTags(int $userId): array
    {
        $rows = $this->getDatabase()->table('tag_user_map')->select('tag_id')
            ->where('user_id', $userId)->orderBy('tag_id')->fetchAll();
        return \array_map(static fn(array $row): int => (int) $row['tag_id'], $rows);
    }
}
