<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity\Buyer;
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

        // Make tables
        $this->makeTable('users', [
            'id' => 'primary',
            'name' => 'string',
        ]);
        $this->makeTable('buyers', [
            'id' => 'primary',
            'address' => 'string',
        ]);
        $this->makeTable(
            table: 'buyer_partners',
            columns: [
                'buyer_id' => 'int',
                'partner_id' => 'int',
            ],
            pk: ['buyer_id', 'partner_id'],
        );

        $this->loadSchema(__DIR__ . '/schema.php');

        $this->getDatabase()->table('users')->insertMultiple(
            ['id', 'name'],
            [[1, 'John'], [2, 'Sam'], [3, 'Paul']],
        );
        $this->getDatabase()->table('buyers')->insertMultiple(
            ['id', 'address'],
            [[1, 'foo'], [2, 'bar'], [3, 'baz']],
        );
        $this->getDatabase()->table('buyer_partners')->insertMultiple(
            ['buyer_id', 'partner_id'],
            [[1, 2], [1, 3]],
        );
    }

    public function testSelect(): void
    {
        /** @var Buyer $buyer */
        $buyer = (new Select($this->orm, Buyer::class))
            ->wherePK(1)
            ->fetchOne();

        // It's important. $buyer->partners - will trigger relation load and we test it
        $this->assertEquals([
            new Buyer(id: 2, name: 'Sam', address: 'bar'),
            new Buyer(id: 3, name: 'Paul', address: 'baz'),
        ], $buyer->partners);
    }
}
