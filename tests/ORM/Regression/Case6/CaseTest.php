<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case6;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Regression\Case6\Entity\User;
use Cycle\ORM\Tests\Regression\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    #[\Testo\Test]
    public function testSelect(): void
    {
        /** @var User $model */
        $model = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::same($model->getLogin(), 'foo');
        \Testo\Expect::exception(\Error::class)->withMessageContaining('Cannot access non-public property ' . User::class . '::$login');

        $model->login = 'new login';
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        // Init DB
        parent::setUp();

        // Make tables
        $this->makeTable('users', [
            'id' => 'int,primary',
            'login' => 'string',
        ]);

        $this->loadSchema(__DIR__ . '/schema.php');

        $this->getDatabase()->table('users')->insertMultiple(
            ['id', 'login'],
            [
                [1, 'foo'],
            ],
        );
    }
}
