<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\ORM\Schema;
use Spiral\ORM\Tests\Fixtures\Profile;
use Spiral\ORM\Tests\Fixtures\User;

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
     * @expectedException \Spiral\ORM\Exception\SchemaException
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

    public function testRelationsSchema()
    {
        $schema = new Schema([
            User::class => [
                Schema::RELATIONS => [
                    1 => ['value']
                ]
            ]
        ]);

        $this->assertSame(['value'], $schema->defineRelation(User::class, 1));
    }

    /**
     * @expectedException \Spiral\ORM\Exception\SchemaException
     */
    public function testSchemaException3()
    {
        $schema = new Schema([
            User::class => [
                Schema::RELATIONS => [
                    1 => ['value']
                ]
            ]
        ]);

        $schema->defineRelation(User::class, 2);
    }
}