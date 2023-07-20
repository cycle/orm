<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue424;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue424\Entity\User;
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

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testPersistStateTwice(): void
    {
        $user = new User();
        $user->login = 'test';
        $user->passwordHash = 'pass';

        $em = new EntityManager($this->orm);

        $em->persistState($user);
        $user->passwordHash = 'new-password-42';
        $em->persistState($user);

        $em->run();

        $saved = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();

        $this->assertSame('new-password-42', $saved->passwordHash);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('users', [
            'id' => 'primary',
            'login' => 'string',
            'password_hash' => 'string',
        ]);
    }
}
