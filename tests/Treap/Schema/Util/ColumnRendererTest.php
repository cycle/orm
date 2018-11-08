<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Tests\Schema\Util;

use Spiral\ORM\Helpers\ColumnRenderer;
use Spiral\Tests\ORM\Fixtures\User;

abstract class ColumnRendererTest extends BaseTest
{
    public function testNamedIndexes()
    {
        $table = $this->orm->table(User::class);
        $this->assertSame('status_index', $table->getSchema()->index(['status'])->getName());
    }


    public function testRenderString()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'string'
        ], [
            'name' => 'default'
        ], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame('default', $column->getDefaultValue());
        $this->assertFalse($column->isNullable());
    }

    public function testRenderStringNullDefault()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'string'
        ], [
            'name' => null
        ], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(null, $column->getDefaultValue());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderStringNullDeclared()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'string,null'
        ], [], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderStringNullableDeclared()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'string,nullable'
        ], [], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderEnum()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'enum(active,disabled)'
        ], [], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame('active', $column->getDefaultValue());

        $this->assertFalse($column->isNullable());
    }

    public function testRenderEnumNullDefault()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'enum(active,disabled)'
        ], [
            'name' => null
        ], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame(null, $column->getDefaultValue());

        $this->assertTrue($column->isNullable());
    }

    public function testRenderEnumNullSecond()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'id'   => 'primary',
            'name' => 'enum(active,disabled)'
        ], [
            'name' => 'disabled'
        ], $table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame('disabled', $column->getDefaultValue());

        $this->assertFalse($column->isNullable());
    }

    /**
     * @expectedException \Spiral\ORM\Exceptions\DefinitionException
     * @expectedExceptionMessage Invalid column type definition in 'tests_sample'.'column'
     */
    public function testRenderBadDeclaration()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'column' => '~',
        ], [], $table);
    }

    /**
     * @expectedException \Spiral\ORM\Exceptions\DefinitionException
     * @expectedExceptionMessage Invalid column type definition in 'tests_sample'.'column'
     */
    public function testRenderBadDeclaration2()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'column' => 'enum(a',
        ], [], $table);
    }

    /**
     * @expectedException \Spiral\ORM\Exceptions\DefinitionException
     * @expectedExceptionMessage Invalid column type definition in 'tests_sample'.'column'
     */
    public function testRenderBadDeclaration3()
    {
        $table = $this->db->sample->getSchema();

        $renderer = new ColumnRenderer();

        $renderer->renderColumns([
            'column' => 'master',
        ], [], $table);
    }
}