<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);


namespace Cycle\ORM\Tests;

use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\UserSnapshotMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class FollowupTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('user_snapshots', [
            'id'      => 'primary',
            'user_id' => 'int',
            'at'      => 'datetime',
            'action'  => 'string',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => UserSnapshotMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testSnapUser()
    {
        $u = new User();
        $u->email = 'email';
        $u->balance = 100;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(2);

        $snap = $this->getSnap($u);
        $this->assertSame('create', $snap['action']);
        $this->assertSame('email', $snap['email']);
    }

    public function testSnapAgain()
    {
        $u = new User();
        $u->email = 'email';
        $u->balance = 100;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(2);

        $snap = $this->getSnap($u);
        $this->assertSame('create', $snap['action']);
        $this->assertSame('email', $snap['email']);

        $u->email = 'new-email';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(2);

        $snap = $this->getSnap($u);
        $this->assertSame('update', $snap['action']);
        $this->assertSame('new-email', $snap['email']);
    }

    protected function getSnap(User $u): array
    {
        return $this->dbal->database()
                          ->table('user_snapshots')
                          ->select('*')
                          ->where('user_id', $u->id)
                          ->orderBy('id', "DESC")
                          ->limit(1)
                          ->fetchAll()[0];
    }
}