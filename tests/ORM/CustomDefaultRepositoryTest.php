<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Exception\RepositoryException;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\CorrectRepository;
use Cycle\ORM\Tests\Fixtures\IncorrectRepository;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;


class CustomDefaultRepositoryTest extends TestCase
{
    private $orm;

    protected function setUp()
    {
        $this->orm = (new ORM(new Factory(new DatabaseManager(new DatabaseConfig()))));
    }

    public function testShouldThrowExceptionIfRepositoryClassNotImplementedRepositoryInterface()
    {
        $this->expectException(RepositoryException::class);

        $this->orm->withDefaultRepository(IncorrectRepository::class);
    }

    public function testShouldNotThrowExceptionIfRepositoryClassImplementedRepositoryInterface()
    {
        $this->orm->withDefaultRepository(CorrectRepository::class);

        $this->assertNull($this->getExpectedException());
    }

    public function testShouldReturnCorrectDefaultRepository()
    {
        $this->orm = $this->orm->withSchema(new Schema([
            User::class => [
                1 => 'value',
                Schema::ROLE => 'user'
            ]
        ]))->withDefaultRepository(CorrectRepository::class);

        $this->assertEquals(CorrectRepository::class, get_class($this->orm->getRepository('user')));
    }
}