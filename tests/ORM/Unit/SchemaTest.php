<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testSchema(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        $this->assertSame('value', $schema->define(User::class, SchemaInterface::ENTITY));
    }

    public function testEntity(): void
    {
        $schema = new Schema([
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ]);

        $this->assertSame(User::class, $schema->define('user', SchemaInterface::ENTITY));
        $this->assertSame(User::class, $schema->define(User::class, SchemaInterface::ENTITY));
    }

    public function testSchemaException(): void
    {
        $this->expectException(SchemaException::class);

        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
            ],
        ]);

        $schema->define(Profile::class, SchemaInterface::ENTITY);
    }

    public function testSchemaNull(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        $this->assertNull($schema->define(User::class, SchemaInterface::MAPPER));
    }

    public function testToArray()
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => User::class,
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        $this->assertSame([
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ], $schema->toArray());
    }
}
