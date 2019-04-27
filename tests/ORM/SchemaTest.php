<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testSchema()
    {
        $schema = new Schema([
            User::class => [
                1            => 'value',
                Schema::ROLE => 'user'
            ]
        ]);

        $this->assertSame('value', $schema->define(User::class, 1));
    }

    /**
     * @expectedException \Cycle\ORM\Exception\SchemaException
     */
    public function testSchemaException()
    {
        $schema = new Schema([
            User::class => [
                1 => 'value'
            ]
        ]);

        $schema->define(Profile::class, 1);
    }

    public function testSchemaNull()
    {
        $schema = new Schema([
            User::class => [
                1            => 'value',
                Schema::ROLE => 'user'
            ]
        ]);

        $this->assertNull($schema->define(User::class, 2));
    }
}