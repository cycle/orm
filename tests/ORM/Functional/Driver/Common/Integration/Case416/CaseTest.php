<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;
use Ramsey\Uuid\Uuid;

/**
 * @link https://github.com/cycle/orm/issues/416
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
        $uuid = Uuid::uuid7();
        $identity = new Entity\Identity($uuid);
        $profile = new Entity\Profile($uuid);
        $account = new Entity\Account($uuid, 'test@mail.com', \md5('password'));
        $identity->profile = $profile;
        $identity->account = $account;

        $this->save($identity);

        // Note: heap cleaning fixes this issue
        // $this->orm->getHeap()->clean();

        // Get entity
        (new Select($this->orm, Entity\Account::class))
            ->load('identity.profile')
            ->wherePK((string)$uuid)
            ->fetchOne();

        // There is no any exception like this:
        // [Cycle\ORM\Exception\MapperException]
        // Can't hydrate an entity because property and value types are incompatible.
        //
        // [TypeError]
        // Cannot assign Cycle\ORM\Reference\Reference to property
        // Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\Profile::$identity of type
        // Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity\Identity
        $this->assertTrue(true);

        // To avoid `Entity and State are not in sync` exception
        $this->orm->getHeap()->clean();
    }

    private function makeTables(): void
    {
        $this->makeTable(Entity\Identity::ROLE, [
            'uuid' => 'string,primary',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);

        $this->makeTable(Entity\Account::ROLE, [
            'uuid' => 'string,primary',
            'email' => 'string',
            'password_hash' => 'string',
            'updated_at' => 'datetime',
        ]);
        $this->makeFK(
            Entity\Account::ROLE,
            'uuid',
            Entity\Identity::ROLE,
            'uuid',
            'NO ACTION',
            'NO ACTION',
        );

        $this->makeTable(Entity\Profile::ROLE, [
            'uuid' => 'string,primary',
            'updated_at' => 'datetime',
            'first_name' => 'string',
            'last_name' => 'string',
        ]);
        $this->makeFK(
            Entity\Profile::ROLE,
            'uuid',
            Entity\Identity::ROLE,
            'uuid',
            'NO ACTION',
            'NO ACTION',
        );
    }
}
