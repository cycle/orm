<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue489;

use Cycle\ORM\Tests\Regression\Issue489\Entity\User;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    #[\Testo\Skip('todo: fix later')]
    public function testSave(): void
    {
        QueryAssert::writes(1, fn() => $this->save(new User()));
    }

    #[\Testo\Lifecycle\BeforeTest]
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
