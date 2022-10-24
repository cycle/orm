<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case3;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case3\Entity\Currency;
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
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSave(): void
    {
        // Get entity
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();
        // Change data
        $user->currency = new Currency('eur', 'EURO');

        // Store changes and calc write queries
        $this->captureWriteQueries();
        $this->save($user);

        // Check write queries count
        $this->assertNumWrites(2);

        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(1)
            ->fetchOne();
        $this->assertSame('eur', $user->currency->code);
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
