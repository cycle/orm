<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasMany;

use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasMany;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class HasManyClasslessTest extends OrmTestCase
{
    public function clearingUnresolvedCommentsDetachesStoredOnes(): void
    {
        $user = $this->orm->get('user', ['id' => 1]);
        $user->comments = [];

        $this->save($user);

        Assert::same($this->commentsOf(1), []);
    }

    public function assigningUnresolvedCommentsOfAnotherUserMovesThem(): void
    {
        $first = $this->orm->get('user', ['id' => 1]);
        $second = $this->orm->get('user', ['id' => 2]);
        $second->comments = $first->comments;

        $this->save($second);

        Assert::same($this->commentsOf(2), ['msg 1', 'msg 2', 'msg 3']);
    }

    public function lazyCommentsReuseEntitiesFromHeap(): void
    {
        $comment = $this->orm->get('comment', ['id' => 2]);
        $user = $this->orm->get('user', ['id' => 1]);

        Assert::same([...$user->comments->fetch()][1], $comment);
    }

    public function castOfEmptyDataIsEmptyArray(): void
    {
        $relation = $this->comments();

        Assert::same($relation->cast(null), []);
        Assert::same($relation->cast([]), []);
    }

    public function castUsesRoleOfEachItem(): void
    {
        $result = $this->comments()->cast([
            ['id' => '1', 'user_id' => '1'],
            [LoaderInterface::ROLE_KEY => 'like', 'id' => '2', 'user_id' => '1'],
        ]);

        Assert::same($result[0]['user_id'], 1);
        Assert::same($result[1]['user_id'], '1');
    }

    public function linkingEntityOfAnotherRoleFails(): never
    {
        $user = $this->orm->make('user', ['email' => 'new@world.com']);
        $user->comments = [$this->orm->make('like', ['comment_id' => null])];

        Expect::exception(RelationException::class);

        $this->save($user);
    }

    public function newCommentCascadesItsOwnRelations(): void
    {
        $user = $this->orm->get('user', ['id' => 2]);
        $comment = $this->orm->make('comment', ['message' => 'new']);
        $comment->likes = [$this->orm->make('like')];
        $user->comments = [$comment];

        $this->save($user);

        Assert::equals(
            $this->getDatabase()->table('like')->select('comment_id')->fetchAll(),
            [['comment_id' => $comment->id]],
        );
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('comment', ['id' => 'primary', 'user_id' => 'integer,nullable', 'message' => 'string']);
        $this->makeTable('like', ['id' => 'primary', 'comment_id' => 'integer,nullable']);

        $this->getDatabase()->table('user')->insertMultiple(['email'], [['hello@world.com'], ['another@world.com']]);
        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [[1, 'msg 1'], [1, 'msg 2'], [1, 'msg 3']],
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
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => 'comment',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                            Relation::ORDER_BY => ['id' => 'ASC'],
                        ],
                    ],
                ],
            ],
            'comment' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'likes' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => 'like',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'comment_id',
                        ],
                    ],
                ],
            ],
            'like' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'like',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'comment_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    private function comments(): HasMany
    {
        return $this->orm->getRelationMap('user')->getRelations()['comments'];
    }

    /**
     * @return list<string> Messages of the comments stored for the user
     */
    private function commentsOf(int $userId): array
    {
        $rows = $this->getDatabase()->table('comment')->select('message')
            ->where('user_id', $userId)->orderBy('id')->fetchAll();
        return \array_column($rows, 'message');
    }
}
