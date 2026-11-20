<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema;

use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserSnapshotMapper;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class FollowupTest extends OrmTestCase
{
    #[Test]
    public function testSnapUser(): void
    {
        $u = new User();
        $u->email = 'email';
        $u->balance = 100;

        QueryAssert::writes(2, fn() => $this->save($u));

        $snap = $this->getSnap($u);
        Assert::same($snap['action'], 'create');
        Assert::same($snap['email'], 'email');
    }

    #[Test]
    public function testSnapAgain(): void
    {
        $u = new User();
        $u->email = 'email';
        $u->balance = 100;

        QueryAssert::writes(2, fn() => $this->save($u));

        $snap = $this->getSnap($u);
        Assert::same($snap['action'], 'create');
        Assert::same($snap['email'], 'email');

        $u->email = 'new-email';

        QueryAssert::writes(2, fn() => $this->save($u));

        $snap = $this->getSnap($u);
        Assert::same($snap['action'], 'update');
        Assert::same($snap['email'], 'new-email');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('user_snapshots', [
            'id' => 'primary',
            'user_id' => 'int',
            'at' => 'datetime',
            'action' => 'string',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => UserSnapshotMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    protected function getSnap(User $u): array
    {
        return $this->dbal->database()
            ->table('user_snapshots')
            ->select('*')
            ->where('user_id', $u->id)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->fetchAll()[0];
    }
}
