<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class SchemaTest
{
    #[\Testo\Test]
    public function testSchema(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        \Testo\Assert::same($schema->define(User::class, SchemaInterface::ENTITY), 'value');
    }

    #[\Testo\Test]
    public function testEntity(): void
    {
        $schema = new Schema([
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ]);

        \Testo\Assert::same($schema->define('user', SchemaInterface::ENTITY), User::class);
        \Testo\Assert::same($schema->define(User::class, SchemaInterface::ENTITY), User::class);
    }

    #[\Testo\Test]
    public function testSchemaException(): void
    {
        \Testo\Expect::exception(SchemaException::class);

        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
            ],
        ]);

        $schema->define(Profile::class, SchemaInterface::ENTITY);
    }

    #[\Testo\Test]
    public function testSchemaNull(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        \Testo\Assert::null($schema->define(User::class, SchemaInterface::MAPPER));
    }

    #[\Testo\Test]
    public function testToArray()
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => User::class,
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        \Testo\Assert::same($schema->toArray(), [
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ]);
    }
}
