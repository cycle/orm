<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue489;

use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Regression\IntegrationTestTrait;
use Cycle\ORM\Tests\Regression\Issue489\Entity\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSave(): void
    {
        $this->markTestSkipped('todo: fix later');
        $this->captureWriteQueries();
        $this->save(new User());
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
            'user_id' => 'int,nullable',
        ]);
    }
}
