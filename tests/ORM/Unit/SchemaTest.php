<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SchemaTest
{
    public function testSchema(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => 'value',
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        Assert::same($schema->define(User::class, SchemaInterface::ENTITY), 'value');
    }

    public function testEntity(): void
    {
        $schema = new Schema([
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ]);

        Assert::same($schema->define('user', SchemaInterface::ENTITY), User::class);
        Assert::same($schema->define(User::class, SchemaInterface::ENTITY), User::class);
    }

    public function testSchemaException(): void
    {
        Expect::exception(SchemaException::class);

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

        Assert::null($schema->define(User::class, SchemaInterface::MAPPER));
    }

    public function testToArray(): void
    {
        $schema = new Schema([
            User::class => [
                SchemaInterface::ENTITY => User::class,
                SchemaInterface::ROLE => 'user',
            ],
        ]);

        Assert::same($schema->toArray(), [
            'user' => [
                SchemaInterface::ENTITY => User::class,
            ],
        ]);
    }
}
