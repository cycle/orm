<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Embedded;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class EmbeddedBulkLoaderTest extends OrmTestCase
{
    #[Test]
    public function embeddedOfRemovedRowIsTakenFromHeap(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $user = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();
        $credentials = (new Select($this->orm, UserCredentials::class))->wherePK(1)->fetchOne();
        $this->getDatabase()->table('user')->delete(['id' => 1])->run();

        (new BulkLoader($this->orm))->collect($user)->load('credentials')->run();

        Assert::same($this->orm->getMapper(User::class)->fetchRelations($user)['credentials'], $credentials);
    }

    #[Test]
    public function embeddedOfRemovedRowIsResolvedToNull(): void
    {
        $this->orm = $this->orm->withHeap(new Heap());
        $user = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();
        $this->getDatabase()->table('user')->delete(['id' => 1])->run();

        (new BulkLoader($this->orm))->collect($user)->load('credentials')->run();

        $reference = $this->orm->getMapper(User::class)->fetchRelations($user)['credentials'];
        Assert::instanceOf($reference, ReferenceInterface::class);
        Assert::true($reference->hasValue());
        Assert::null($reference->getValue());

        $this->captureReadQueries();
        Assert::null($user->credentials);
        $this->assertNumReads(0);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'creds_username' => 'string',
            'creds_password' => 'string',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'creds_username', 'creds_password'],
            [
                ['hello@world.com', 100, 'user1', 'pass1'],
                ['another@world.com', 200, 'user2', 'pass2'],
            ],
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
                    'credentials' => [
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => 'user:credentials',
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ],
            ],
            UserCredentials::class => [
                Schema::ROLE => 'user:credentials',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'username' => 'creds_username',
                    'password' => 'creds_password',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
