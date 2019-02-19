<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;

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