<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue489;

use Cycle\ORM\Tests\Regression\Issue489\Entity\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Lifecycle\BeforeTest;
use Testo\Skip;
use Testo\Test;

final class CaseTest extends OrmTestCase
{
    #[Test]
    #[Skip('todo: fix later')]
    public function testSave(): void
    {
        $this->captureWriteQueries();
        $this->save(new User());
        $this->assertNumWrites(1);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        // Init DB
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
