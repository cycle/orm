<?php


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
    public function testShouldThrowExceptionIfRepositoryClassNotImplementedRepositoryInterface()
    {
        $this->expectException(RepositoryException::class);

        (new ORM(new Factory(new DatabaseManager(new DatabaseConfig()))))->withDefaultRepository(IncorrectRepository::class);
    }

    public function testShouldNotThrowExceptionIfRepositoryClassImplementedRepositoryInterface()
    {
        (new ORM(new Factory(new DatabaseManager(new DatabaseConfig()))))->withDefaultRepository(CorrectRepository::class);

        $this->assertNull($this->getExpectedException());
    }

    public function testShouldReturnCorrectDefaultRepository()
    {
        $orm = (new ORM(new Factory(new DatabaseManager(new DatabaseConfig()))))->withSchema(new Schema([
            User::class => [
                1 => 'value',
                Schema::ROLE => 'user'
            ]
        ]))->withDefaultRepository(CorrectRepository::class);

        $this->assertEquals(CorrectRepository::class, get_class($orm->getRepository('user')));
    }
}