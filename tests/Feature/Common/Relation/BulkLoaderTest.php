<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class BulkLoaderTest extends OrmTestCase
{
    public function collectAcceptsNamedArguments(): void
    {
        [$first, $second] = $this->users();

        (new BulkLoader($this->orm))->collect(...['first' => $first, 'second' => $second])->load('comments')->run();

        Assert::same($this->messages($first), ['msg 1', 'msg 2']);
        Assert::same($this->messages($second), ['msg 3']);
    }

    public function repeatedCollectKeepsEarlierEntities(): void
    {
        [$first, $second] = $this->users();

        (new BulkLoader($this->orm))->collect($first)->collect($second)->load('comments')->run();

        Assert::same($this->messages($first), ['msg 1', 'msg 2']);
        Assert::same($this->messages($second), ['msg 3']);
    }

    public function loadedRelationIsKeptAndOthersAreFilled(): void
    {
        $first = (new Select($this->orm, 'user'))->load('comments')->wherePK(1)->fetchOne();
        $comments = $first->comments;
        $second = $this->orm->get('user', ['id' => 2]);

        (new BulkLoader($this->orm))->collect($first, $second)->load('comments')->run();

        Assert::same($first->comments, $comments);
        Assert::same($this->messages($second), ['msg 3']);
    }

    public function nodeWithoutKeyFails(): never
    {
        $user = new \stdClass();
        $this->orm->getHeap()->attach($user, new Node(Node::MANAGED, ['email' => 'x@world.com'], 'user'));

        Expect::exception(\LogicException::class)->withMessage('Bulk loader cannot get the value for the key `id`.');

        (new BulkLoader($this->orm))->collect($user)->load('comments')->run();
    }

    public function nonScalarKeyFails(): never
    {
        $user = new \stdClass();
        $this->orm->getHeap()->attach($user, new Node(Node::MANAGED, ['id' => [1], 'email' => 'x@world.com'], 'user'));

        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('Invalid value on the primary key `id`. Expected scalar, got array.');

        (new BulkLoader($this->orm))->collect($user)->run();
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('comment', ['id' => 'primary', 'user_id' => 'integer', 'message' => 'string']);

        $this->getDatabase()->table('user')->insertMultiple(['email'], [['hello@world.com'], ['another@world.com']]);
        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [[1, 'msg 1'], [1, 'msg 2'], [2, 'msg 3']],
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
                Schema::RELATIONS => [],
            ],
        ]));
    }

    /**
     * @return list<object> Both users, their comments not loaded
     */
    private function users(): array
    {
        return (new Select($this->orm, 'user'))->orderBy('id')->fetchAll();
    }

    /**
     * Messages of the comments the user holds, failing when the relation was left unresolved.
     *
     * @return list<string>
     */
    private function messages(object $user): array
    {
        Assert::false($user->comments instanceof ReferenceInterface);
        return \array_map(static fn(object $c): string => $c->message, [...$user->comments]);
    }
}
