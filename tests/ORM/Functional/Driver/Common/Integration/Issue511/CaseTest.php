<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue511;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * @link https://github.com/cycle/orm/issues/511
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSelect(): void
    {
        (new Select($this->orm, Entity\User::class))
            ->load('visitPermission')
            ->load('visitPermission.cities')
            ->load('passport')
            ->fetchAll();

        $this->assertTrue(true);
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'login' => 'string',
        ]);

        $this->makeTable('passport', [
            'id' => 'primary',
            'number' => 'string',
            'user_id' => 'int',
        ]);
        $this->makeFK('passport', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('city', [
            'id' => 'primary',
            'name' => 'string',
        ]);

        $this->makeTable('user_visit_permission', [
            'id' => 'primary',
            'user_id' => 'int',
            'all_cities' => 'bool',
        ]);
        $this->makeFK('user_visit_permission', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('user_visit__permission_city', [
            'id' => 'primary',
            'user_id' => 'int',
            'city_id' => 'int',
        ]);
        $this->makeFK('user_visit__permission_city', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('user_visit__permission_city', 'city_id', 'city', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['login'],
            [
                ['user-1'],
            ],
        );

        $this->getDatabase()->table('passport')->insertMultiple(
            ['user_id', 'number'],
            [
                [1, '123456'],
            ],
        );
    }
}
