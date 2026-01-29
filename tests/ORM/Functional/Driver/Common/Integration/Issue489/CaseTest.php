<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue489;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue489\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSave(): void
    {
        $this->captureWriteQueries();
        $em = new EntityManager($this->orm);

        $user = new User();

        $em->persist($user);
        $em->run();

        // Check write queries count
        $this->assertNumWrites(1);
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable(User::ROLE, [
            'id' => 'primary', // autoincrement
            'user_id' => 'int',
        ]);
        $this->makeFK(User::ROLE, 'user_id', User::ROLE, 'id', 'CASCADE', 'CASCADE');
    }
}
