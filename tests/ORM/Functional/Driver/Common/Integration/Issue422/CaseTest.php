<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue422\Entity\User;
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

    public function testSelect(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('billing')
            ->wherePK(1)
            ->fetchOne();
        self::assertNotNull($user->billing);

        /** @var User $user */
        $user = (new Select($this->orm, Entity\User::class))
            ->load('billing')
            ->wherePK(2)
            ->fetchOne();
        self::assertNull($user->billing);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('user', [
            'id' => 'primary',
            'name' => 'string',
        ]);

        $this->makeTable('billing', [
            'id' => 'primary',
            'user_id' => 'int',
            'property_string' => 'string',
            'property_int' => 'int',
        ]);
        $this->makeFK('billing', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['name'],
            [
                ['user-with-billing'],
                ['user-without-billing'],
            ],
        );
        $this->getDatabase()->table('billing')->insertMultiple(
            ['user_id', 'property_string', 'property_int'],
            [
                [1, 'foo', 100],
            ],
        );
    }
}
