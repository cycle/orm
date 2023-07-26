<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case318;

use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;
use Ramsey\Uuid\Uuid;

/**
 * @link https://github.com/cycle/orm/issues/318
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelect(): void
    {
        $groupUuid = Uuid::uuid4();
        $userUuid = Uuid::uuid4();

        $this
            ->getDatabase()
            ->table('users')
            ->insertOne(['uuid' => $userUuid->toString(), 'login' => 'user_1']);
        $this
            ->getDatabase()
            ->table('groups')
            ->insertMultiple(['uuid', 'title'], [[$groupUuid->toString(), 'group_1']]);
        $this
            ->getDatabase()
            ->table('user_groups')
            ->insertMultiple(['user_uuid', 'group_uuid'], [[$userUuid->toString(), $groupUuid->toString()]]);

        $user = $this->orm->getRepository(Entity\User::class)
            ->findByPK($userUuid);
        $groups = $user->groups;

        $this->assertEquals($groupUuid, $groups[0]->uuid);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('users', [
            'uuid' => 'string',
            'login' => 'string',
        ], pk: ['uuid']);

        $this->makeTable('groups', [
            'uuid' => 'string',
            'title' => 'string',
        ], pk: ['uuid']);

        $this->makeTable('user_groups', [
            'user_uuid' => 'string',
            'group_uuid' => 'string',
        ], pk: ['user_uuid', 'group_uuid']);
    }
}
