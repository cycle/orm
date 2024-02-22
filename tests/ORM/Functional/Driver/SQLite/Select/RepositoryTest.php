<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Select\RepositoryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class RepositoryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    /**
     * This test does not need to be executed for each database driver.
     */
    public function testForUpdate(): void
    {
        $repository = $this->orm->getRepository(User::class);

        $this->assertFalse($repository->select()->getBuilder()->getQuery()->getTokens()['forUpdate']);

        $forUpdate = $repository->forUpdate();

        $this->assertTrue($forUpdate->select()->getBuilder()->getQuery()->getTokens()['forUpdate']);
        $this->assertNotSame($repository, $forUpdate);
    }
}
