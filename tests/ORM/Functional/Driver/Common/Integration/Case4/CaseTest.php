<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
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

        $this->loadSchema(__DIR__.'/schema.php');
    }

    public function testOnce(): void
    {
        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        $em = (new EntityManager($this->orm));
        $em->persist($user);

        $i = 0;

        $em->persist(new User\Alias($user, (string) ++$i));
        $em->persist(new User\Alias($user, (string) ++$i));

        $em->persist(new User\Email($user, (string) ++$i));
        $em->persist(new User\Email($user, (string) ++$i));

        $em->persist(new User\Phone($user, (string) ++$i));
        $em->persist(new User\Phone($user, (string) ++$i));

        $em->run();

        $db = $this->orm->getSource(User::class)->getDatabase();
        self::assertSame(
            [
                'alias' => [
                    ['value' => '1'],
                    ['value' => '2'],
                ],
                'email' => [
                    ['value' => '3'],
                    ['value' => '4'],
                ],
                'phone' => [
                    ['value' => '5'],
                    ['value' => '6'],
                ],
            ],
            [
                'alias' => $db->select('value')->from('user_alias')->fetchAll(),
                'email' => $db->select('value')->from('user_email')->fetchAll(),
                'phone' => $db->select('value')->from('user_phone')->fetchAll(),
            ]
        );
    }

    /**
     * @dataProvider dataMatrix
     */
    public function testMatrix(int $cnt1, int $cnt2, int $cnt3): void
    {
        /** @var User $user */
        $user = $this->orm->getRepository(User::class)->findOne(['id' => 1]);

        $em = (new EntityManager($this->orm));
        $em->persist($user);

        $i = 0;

        $expected = [];
        while ($cnt1-- > 0) {
            $em->persist(new User\Alias($user, $v = (string) ++$i));
            $expected['alias'][] = ['value' => $v];
        }

        while ($cnt2-- > 0) {
            $em->persist(new User\Email($user, $v = (string) ++$i));
            $expected['email'][] = ['value' => $v];
        }

        while ($cnt3-- > 0) {
            $em->persist(new User\Phone($user, $v = (string) ++$i));
            $expected['phone'][] = ['value' => $v];
        }
        $em->run();

        $db = $this->orm->getSource(User::class)->getDatabase();
        self::assertSame(
            $expected,
            [
                'alias' => $db->select('value')->from('user_alias')->fetchAll(),
                'email' => $db->select('value')->from('user_email')->fetchAll(),
                'phone' => $db->select('value')->from('user_phone')->fetchAll(),
            ]
        );
    }

    public function dataMatrix(): iterable
    {
        yield [2, 2, 2];
        yield [3, 3, 1];
        yield [1, 7, 4];
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

        $this->makeTable('user_email', [
            'id' => 'primary',
            'value' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('user_email', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('user_phone', [
            'id' => 'primary',
            'value' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('user_phone', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
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
