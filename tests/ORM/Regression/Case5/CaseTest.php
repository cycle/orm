<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case5;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Case5\Entity\Buyer;
use Cycle\Testing\ORM\OrmTestCase;

class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSelect(): void
    {
        /** @var Buyer $buyer */
        $buyer = (new Select($this->orm, Buyer::class))
            ->wherePK(1)
            ->fetchOne();

        // It's important. $buyer->partners - will trigger relation load and we test it
        \Testo\Assert::true($buyer->partners->exists(function (int $key, Buyer $element): bool {
            return $element->id === 2;
        }));
        \Testo\Assert::true($buyer->partners->exists(function (int $key, Buyer $element): bool {
            return $element->id === 3;
        }));
        \Testo\Assert::count($buyer->partners, 2);
        \Testo\Assert::same($buyer->badge->label, 'lab');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        // Init DB
        // Make tables
        $this->makeTable('case_5_users', [
            'id' => 'primary',
            'name' => 'string',
        ]);
        $this->makeTable('case_5_buyers', [
            'id' => 'primary',
            'address' => 'string',
        ]);
        $this->makeTable('badge_table', [
            'id' => 'primary',
            'label' => 'string',
        ]);
        $this->makeTable(
            table: 'case_5_buyer_partners',
            columns: [
                'buyer_id' => 'int',
                'partner_id' => 'int',
            ],
            pk: ['buyer_id', 'partner_id'],
        );

        $this->loadSchema(__DIR__ . '/schema.php');

        $this->getDatabase()->table('case_5_users')->insertMultiple(
            ['name'],
            [['John'], ['Sam'], ['Paul']],
        );
        $this->getDatabase()->table('case_5_buyers')->insertMultiple(
            ['address'],
            [['foo'], ['bar'], ['baz']],
        );
        $this->getDatabase()->table('badge_table')->insertMultiple(
            ['label'],
            [['lab'], ['bal'], ['abl']],
        );
        $this->getDatabase()->table('case_5_buyer_partners')->insertMultiple(
            ['buyer_id', 'partner_id'],
            [[1, 2], [1, 3]],
        );
    }
}
