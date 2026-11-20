<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case6;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Case6\Entity\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class CaseTest extends OrmTestCase
{
    #[Test]
    public function testSelect(): void
    {
        /** @var User $model */
        $model = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->fetchOne();

        Assert::same($model->getLogin(), 'foo');
        Expect::exception(\Error::class)->withMessageContaining('Cannot access non-public property ' . User::class . '::$login');

        $model->login = 'new login';
    }

    #[BeforeTest]
    public function setUp(): void
    {
        // Init DB
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
