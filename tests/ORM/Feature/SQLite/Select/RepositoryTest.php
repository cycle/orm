<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\Select\RepositoryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class RepositoryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    /**
     * This test does not need to be executed for each database driver.
     */
    #[\Testo\Test]
    public function testForUpdate(): void
    {
        $repository = $this->orm->getRepository(User::class);

        \Testo\Assert::false($repository->select()->getBuilder()->getQuery()->getTokens()['forUpdate']);

        $forUpdate = $repository->forUpdate();

        \Testo\Assert::true($forUpdate->select()->getBuilder()->getQuery()->getTokens()['forUpdate']);
        \Testo\Assert::notSame($forUpdate, $repository);
    }
}
