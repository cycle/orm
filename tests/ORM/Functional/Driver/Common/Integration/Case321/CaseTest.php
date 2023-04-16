<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
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

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function test1(): void
    {
        $user = new Entity\User1();

        // Store changes and calc write queries
        $this->captureWriteQueries();
        $this->save($user);

        // Check write queries count
        $this->assertNumWrites(1);
    }

    public function test2(): void
    {
        $user = new Entity\User2();

        // Store changes and calc write queries
        $this->captureWriteQueries();
        $this->save($user);

        // Check write queries count
        $this->assertNumWrites(1);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('user1', [
            'id' => 'primary', // autoincrement
        ]);

        $this->makeTable('user2', [
            'id' => 'primary', // autoincrement
        ]);
    }
}
