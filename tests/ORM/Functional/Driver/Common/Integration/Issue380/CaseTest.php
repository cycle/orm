<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue380;

use Cycle\Database\Exception\StatementException\ConstrainException;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue380\Entity\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testInsertOnce(): void
    {
        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        $em = (new EntityManager($this->orm));
        $em->persist($user);

        $i = 0;

        $em->persist(new User\Alias($user, (string)++$i));
        $em->persist(new User\Alias($user, (string)++$i));

        $em->persist(new User\Email($user, (string)++$i));
        $em->persist(new User\Email($user, (string)++$i));

        $em->persist(new User\Phone($user, (string)++$i));
        $em->persist(new User\Phone($user, (string)++$i));

        $em->run();

        self::assertSame(
            [
                'alias' => [
                    ['id' => 1, 'value' => '1'],
                    ['id' => 2, 'value' => '2'],
                ],
                'email' => [
                    ['id' => 1, 'value' => '3'],
                    ['id' => 2, 'value' => '4'],
                ],
                'phone' => [
                    ['id' => 1, 'value' => '5'],
                    ['id' => 2, 'value' => '6'],
                ],
            ],
            [
                'alias' => $this->fetchFromTable('user_alias'),
                'email' => $this->fetchFromTable('user_email'),
                'phone' => $this->fetchFromTable('user_phone'),
            ]
        );
    }

    /**
     * @dataProvider dataMatrix
     */
    public function testInsertMatrix(int $cnt1, int $cnt2, int $cnt3): void
    {
        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        $em = (new EntityManager($this->orm));
        $em->persist($user);

        $i = 0;

        $expected = [];
        for ($id = 1; $id <= $cnt1; $id++) {
            $em->persist(new User\Alias($user, $v = (string)++$i));
            $expected['alias'][] = ['id' => $id, 'value' => $v];
        }

        for ($id = 1; $id <= $cnt2; $id++) {
            $em->persist(new User\Email($user, $v = (string)++$i));
            $expected['email'][] = ['id' => $id, 'value' => $v];
        }

        for ($id = 1; $id <= $cnt3; $id++) {
            $em->persist(new User\Phone($user, $v = (string)++$i));
            $expected['phone'][] = ['id' => $id, 'value' => $v];
        }
        $em->run();

        self::assertSame(
            $expected,
            [
                'alias' => $this->fetchFromTable('user_alias'),
                'email' => $this->fetchFromTable('user_email'),
                'phone' => $this->fetchFromTable('user_phone'),
            ]
        );
    }

    public function dataMatrix(): iterable
    {
        yield [2, 2, 2];
        yield [3, 3, 1];
        yield [1, 7, 4];
    }

    public function testFailOnInsertUniqueDuplicate(): void
    {
        self::expectException(ConstrainException::class);

        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        // insert unique
        $em = (new EntityManager($this->orm));
        $em->persist($user);
        $em
            ->persist(new User\Alias($user, '1'))
            ->persist(new User\Alias($user, '1'))
            ->run();
    }

    public function testDeleteAndInsertFainOnDuplicateUniqueKey(): void
    {
        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        // insert unique
        $em = (new EntityManager($this->orm));
        $em->persist($user);
        $em
            ->persist($a0 = new User\Alias($user, '1'))
            ->persist($a1 = new User\Alias($user, '2'));
        $em
            ->persist($e0 = new User\Email($user, '1'))
            ->persist($e1 = new User\Email($user, '2'));
        $em
            ->persist($p0 = new User\Phone($user, '1'))
            ->persist($p1 = new User\Phone($user, '2'));
        $em->run();

        self::assertCount(2, \array_intersect([1, 2], [$a0->id, $a1->id]));
        self::assertCount(2, \array_intersect([1, 2], [$e0->id, $e1->id]));
        self::assertCount(2, \array_intersect([1, 2], [$p0->id, $p1->id]));
        unset($a0, $a1, $e0, $e1, $p0, $p1);

        $this->orm->getHeap()->clean();

        // delete old and persist new with same unique value
        $em = (new EntityManager($this->orm));

        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);
        $user->username = 'up-username';
        $em->persist($user);

        $a0 = $this->orm->get(User\Alias::class, ['id' => 1]);
        $a1 = $this->orm->get(User\Alias::class, ['id' => 2]);

        $e0 = $this->orm->get(User\Email::class, ['id' => 1]);
        $e1 = $this->orm->get(User\Email::class, ['id' => 2]);

        $p0 = $this->orm->get(User\Phone::class, ['id' => 1]);
        $p1 = $this->orm->get(User\Phone::class, ['id' => 2]);

        $em
            ->delete($a0)
            ->delete($a1);
        $em
            ->persist(new User\Alias($user, '1'))
            ->persist(new User\Alias($user, '2'));
        $em
            ->delete($e0)
            ->delete($e1);
        $em
            ->persist(new User\Email($user, '1'))
            ->persist(new User\Email($user, '2'));
        $em
            ->delete($p0)
            ->delete($p1);
        $em
            ->persist(new User\Phone($user, '1'))
            ->persist(new User\Phone($user, '2'));

        $em->run();

        self::assertTrue(true);
    }

    private function fetchFromTable(string $tableName): array
    {
        $db = $this->orm->getSource(User::class)->getDatabase();
        $rows = $db
            ->select('id', 'value')
            ->from($tableName)
            ->orderBy('id')
            ->fetchAll();
        // cast id to int specially for mssql
        return \array_map(function (array $row): array {
            $row['id'] = (int)$row['id'];
            return $row;
        }, $rows);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('user', [
            'id' => 'primary', // autoincrement
            'username' => 'string',
            'age' => 'int',
        ]);

        $this->makeTable('user_alias', [
            'id' => 'primary',
            'value' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('user_alias', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeIndex('user_alias', ['value'], true);

        $this->makeTable('user_email', [
            'id' => 'primary',
            'value' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('user_email', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeIndex('user_email', ['value'], true);

        $this->makeTable('user_phone', [
            'id' => 'primary',
            'value' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('user_phone', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeIndex('user_phone', ['value'], true);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->delete();
        $this->getDatabase()->table('user_alias')->delete();
        $this->getDatabase()->table('user_email')->delete();
        $this->getDatabase()->table('user_phone')->delete();

        $this->getDatabase()
            ->table('user')
            ->insertOne([
                'username' => 'nobody',
                'age' => 0,
            ]);
    }
}
