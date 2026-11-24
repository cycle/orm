<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Options;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByMsgScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class SelectTest extends OrmTestCase
{
    #[Test]
    public function offsetSkipsRows(): void
    {
        $users = (new Select($this->orm, User::class))->orderBy('id')->limit(1)->offset(1)->fetchAll();

        Assert::count($users, 1);
        Assert::same($users[0]->email, 'another@world.com');
    }

    #[Test]
    public function fetchOneLeavesTheSelectUntouched(): void
    {
        $select = (new Select($this->orm, User::class))->orderBy('id');

        $select->fetchOne(['balance' => 200]);

        Assert::count($select->fetchAll(), 2);
    }

    #[Test]
    public function fetchOneLimitsTheQueryToOneRow(): void
    {
        $queries = $this->context->queryLog()->capture(
            fn(): ?object => (new Select($this->orm, User::class))->fetchOne(),
        )['queries'];

        Assert::count($queries, 1);
        Assert::string($queries[0])->matchesRegex('/LIMIT\s+1\b|BETWEEN\s+1\s+AND\s+1\b/i');
    }

    #[Test]
    public function fetchDataWithoutTypecastHasNoRoleKey(): void
    {
        $rows = (new Select($this->orm, User::class))->orderBy('id')->fetchData(false);

        Assert::same(\array_keys($rows[0]), ['id', 'email', 'balance']);
    }

    #[Test]
    public function groupByIsAddedForJoinedSelectWithLimit(): void
    {
        $select = (new Select($this->dedupOrm(), User::class))->with('comments')->limit(2);

        Assert::string($select->sqlStatement())->contains('GROUP BY');
    }

    #[Test]
    public function groupByIsAddedForJoinedSelectWithOffset(): void
    {
        $select = (new Select($this->dedupOrm(), User::class))->with('comments')->limit(1)->offset(1);

        Assert::string($select->sqlStatement())->contains('GROUP BY');
    }

    #[Test]
    public function groupByIsSkippedForSingleRowLimit(): void
    {
        $select = (new Select($this->dedupOrm(), User::class))->with('comments')->limit(1);

        Assert::string($select->sqlStatement())->notContains('GROUP BY');
    }

    #[Test]
    public function groupByIsSkippedWithoutJoins(): void
    {
        $select = (new Select($this->dedupOrm(), User::class))->limit(2);

        Assert::string($select->sqlStatement())->notContains('GROUP BY');
    }

    #[Test]
    public function groupByIsSkippedWhenDisabled(): void
    {
        $select = (new Select($this->dedupOrm(false), User::class))->with('comments')->limit(2);

        Assert::string($select->sqlStatement())->notContains('GROUP BY');
    }

    #[Test]
    public function groupByDoesNotStickToTheSelect(): void
    {
        $select = (new Select($this->dedupOrm(), User::class))->with('comments')->limit(2);
        $select->sqlStatement();

        $select->limit(1);

        Assert::string($select->sqlStatement())->notContains('GROUP BY');
    }

    #[Test]
    public function groupByDeduplicatesJoinedRows(): void
    {
        $users = (new Select($this->dedupOrm(), User::class))
            ->with('comments')
            ->orderBy('user.id')
            ->limit(2)
            ->fetchAll();

        Assert::same(\array_map(static fn(User $u): string => $u->email, $users), [
            'hello@world.com',
            'another@world.com',
        ]);
    }

    #[Test]
    public function relationCanBeJoinedAndLoadedInOneQuery(): void
    {
        $users = (new Select($this->orm, User::class))
            ->with('comments')
            ->where('comments.message', 'msg 1')
            ->load('comments', ['method' => Select::SINGLE_QUERY])
            ->fetchAll();

        Assert::count($users, 1);
        Assert::count($users[0]->comments, 2);
    }

    #[Test]
    public function relationScopeCanBeGivenAsClassName(): void
    {
        $users = (new Select($this->orm, User::class))
            ->load('comments', ['scope' => SortByMsgScope::class])
            ->orderBy('id')
            ->fetchAll();

        Assert::same(\array_map(static fn(Comment $c): string => $c->message, [...$users[0]->comments]), [
            'msg 1',
            'msg 2',
        ]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);
        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [['hello@world.com', 100], ['another@world.com', 200]],
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ]);
        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [[1, 'msg 2'], [1, 'msg 1'], [2, 'msg 3']],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    private function dedupOrm(bool $enabled = true): ORMInterface
    {
        return $this->orm->with(options: (new Options())->withGroupByToDeduplicate($enabled));
    }
}
