<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case3;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Case3\Entity\Currency;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSave(): void
    {
        // Get entity
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();
        // Change data
        $user->currency = new Currency('eur', 'EURO');
        // Store changes and calc write queries
        QueryAssert::writes(2, fn() => $this->save($user));

        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();
        \Testo\Assert::same($user->currency->code, 'eur');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        // Init DB
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('users', [
            'id' => 'primary', // autoincrement
            'name' => 'string',
            'currency_code' => 'string',
        ]);

        $this->makeTable('currencies', [
            'code' => 'string',
            'name' => 'string',
        ]);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('users')->insertMultiple(
            ['name', 'currency_code'],
            [
                ['John', 'usd'],
            ],
        );
        $this->getDatabase()->table('currencies')->insertMultiple(
            ['code', 'name'],
            [
                ['usd', 'USD'],
            ],
        );
    }
}
