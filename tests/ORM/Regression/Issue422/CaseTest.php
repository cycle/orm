<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue422;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Issue422\Entity\Billing;
use Cycle\ORM\Tests\Regression\Issue422\Entity\User;
use Cycle\Testing\ORM\OrmTestCase;

class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSelect(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->load('billing')
            ->wherePK(1)
            ->fetchOne();
        \Testo\Assert::instanceOf($user->billing, Billing::class);

        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->load('billing')
            ->wherePK(2)
            ->fetchOne();
        \Testo\Assert::null($user->billing);
        \Testo\Assert::same($user->otherEmbedded->propertyInt, 200);
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
        $this->makeTable('user', [
            'id' => 'primary',
            'name' => 'string',
            'property_string' => 'string',
            'property_int' => 'int',
        ]);

        $this->makeTable('billing', [
            'id' => 'primary',
            'user_id' => 'int',
            'property_string' => 'string',
            'property_int' => 'int',
        ]);
        $this->makeFK('billing', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['name', 'property_string', 'property_int'],
            [
                ['user-with-billing', 'foo', 100],
                ['user-without-billing', 'bar', 200],
            ],
        );
        $this->getDatabase()->table('billing')->insertMultiple(
            ['user_id', 'property_string', 'property_int'],
            [
                [1, 'foo', 100],
            ],
        );
    }
}
