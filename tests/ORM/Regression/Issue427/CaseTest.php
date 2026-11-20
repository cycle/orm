<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue427;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Issue427\Entity\Buyer;
use Cycle\ORM\Tests\Regression\Issue427\Entity\User;
use Cycle\Testing\ORM\OrmTestCase;

/**
 * @link https://github.com/cycle/orm/issues/427
 */
class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSelect(): void
    {
        $buyer = (new Select($this->orm, Buyer::class))
            ->wherePK(4)
            ->fetchOne();

        \Testo\Assert::instanceOf($buyer, Buyer::class);
        \Testo\Assert::same($buyer->id, 4);
        \Testo\Assert::same($buyer->address, 'foo');

        \Testo\Assert::count($buyer->partners, 2);

        $user1 = $buyer->partners[0];
        $user2 = $buyer->partners[1];

        \assert($user1 instanceof User);
        \assert($user2 instanceof User);

        \Testo\Assert::same($user1->id->toString(), '00000000-0000-0000-0000-000000000001');
        \Testo\Assert::same($user2->id->toString(), '00000000-0000-0000-0000-000000000002');
        \Testo\Assert::same($user1->name, 'John');
        \Testo\Assert::same($user2->name, 'Sam');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        // Init DB
        // Make tables
        $this->makeTable('case_5_users', [
            'id' => 'string,primary',
            'name' => 'string',
        ]);
        $this->makeTable('case_5_buyers', [
            'id' => 'integer,primary',
            'address' => 'string',
        ]);
        $this->makeTable(
            table: 'case_5_buyer_partners',
            columns: [
                'buyer_id' => 'int',
                'partner_id' => 'string',
            ],
            pk: ['buyer_id', 'partner_id'],
        );

        $this->loadSchema(__DIR__ . '/schema.php');

        $this->getDatabase()->table('case_5_users')->insertMultiple(
            ['id', 'name'],
            [
                ['00000000-0000-0000-0000-000000000001', 'John'],
                ['00000000-0000-0000-0000-000000000002', 'Sam'],
                ['00000000-0000-0000-0000-000000000003', 'Paul'],
            ],
        );
        $this->getDatabase()->table('case_5_buyers')->insertMultiple(
            ['id', 'address'],
            [[4, 'foo'], [5, 'bar'], [6, 'baz']],
        );
        $this->getDatabase()->table('case_5_buyer_partners')->insertMultiple(
            ['buyer_id', 'partner_id'],
            [[4, '00000000-0000-0000-0000-000000000001'], [4, '00000000-0000-0000-0000-000000000002']],
        );
    }
}
