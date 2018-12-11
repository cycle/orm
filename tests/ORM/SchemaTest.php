<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Tests\Fixtures\Profile;
use Spiral\Cycle\Tests\Fixtures\User;

class SchemaTest extends TestCase
{
    public function testSchema()
    {
        $schema = new Schema([
            User::class => [
                1 => 'value'
            ]
        ]);

        $this->assertSame('value', $schema->define(User::class, 1));
    }

    /**
     * @expectedException \Spiral\Cycle\Exception\SchemaException
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
                1 => 'value'
            ]
        ]);

        $this->assertNull($schema->define(User::class, 2));
    }
}