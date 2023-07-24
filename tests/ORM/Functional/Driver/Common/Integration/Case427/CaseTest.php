<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity\Buyer;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case427\Entity\User;
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

    public function testSelect(): void
    {
        $buyer = (new Select($this->orm, Buyer::class))
            ->wherePK(4)
            ->fetchOne();

        $this->assertInstanceOf(Buyer::class, $buyer);
        $this->assertSame(4, $buyer->id);
        $this->assertSame('foo', $buyer->address);

        $this->assertCount(2, $buyer->partners);

        $user1 = $buyer->partners[0];
        $user2 = $buyer->partners[1];

        \assert($user1 instanceof User);
        \assert($user2 instanceof User);

        $this->assertSame('00000000-0000-0000-0000-000000000001', $user1->id->toString());
        $this->assertSame('00000000-0000-0000-0000-000000000002', $user2->id->toString());
        $this->assertSame('John', $user1->name);
        $this->assertSame('Sam', $user2->name);
    }
}
