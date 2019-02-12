<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Select\SourceInterface;
use Spiral\Cycle\Tests\Fixtures\NotDeletedConstrain;
use Spiral\Cycle\Tests\Fixtures\SoftDeletedMapper;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class SoftDeletesTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'deleted_at' => 'datetime,null',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => SoftDeletedMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'deleted_at'],
                Schema::TYPECAST    => [
                    'id'         => 'int',
                    'balance'    => 'float',
                    'deleted_at' => 'datetime'
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [],
                Schema::CONSTRAINS  => [
                    SourceInterface::DEFAULT_CONSTRAIN => NotDeletedConstrain::class
                ]
            ]
        ]));
    }

    public function testCreate()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $s = new Select($this->orm->withHeap(new Heap()), User::class);
        $data = $s->fetchData();

        $this->assertNull($data[0]['deleted_at']);
    }

    public function testDelete()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 199;

        (new Transaction($this->orm))->persist($u)->run();

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $u = $s->fetchOne();

        (new Transaction($orm))->delete($u)->run();

        // must be deleted
        $orm = $this->orm->withHeap(new Heap());
        $s = $orm->getMapper(User::class)->getRepository();
        $this->assertNull($s->findOne());

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->constrain(new NotDeletedConstrain());
        $this->assertNull($s->fetchOne());

        $orm = $this->orm->withHeap(new Heap());
        $s = new Select($orm, User::class);
        $s->constrain(null);
        $this->assertNotNull($s->fetchOne());
    }
}