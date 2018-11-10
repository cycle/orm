<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\Database\Schema\AbstractTable;
use Spiral\ORM\Schema\Util\ColumnRenderer;

abstract class ColumnRendererTest extends BaseTest
{
    public function testRenderString()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'string'
            ],
            [
                'name' => 'default'
            ]
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame('default', $column->getDefaultValue());
        $this->assertFalse($column->isNullable());
    }

    public function testRenderStringNullDefault()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'string'
            ], [
                'name' => null
            ]
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(null, $column->getDefaultValue());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderStringNullDeclared()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'string,null'
            ],
            []
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderStringNullableDeclared()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'string,nullable'
            ],
            []
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertTrue($column->isNullable());
    }

    public function testRenderEnum()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'enum(active,disabled)'
            ],
            []
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame('active', $column->getDefaultValue());

        $this->assertFalse($column->isNullable());
    }

    public function testRenderEnumNullDefault()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'enum(active,disabled)'
            ],
            [
                'name' => null
            ]
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame(null, $column->getDefaultValue());

        $this->assertTrue($column->isNullable());
    }

    public function testRenderEnumNullSecond()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'id'   => 'primary',
                'name' => 'enum(active,disabled)'
            ],
            [
                'name' => 'disabled'
            ]
        );

        $table = $this->reload($table);

        $this->assertTrue($table->hasColumn('name'));
        $column = $table->column('name');

        $this->assertSame('string', $column->phpType());
        $this->assertSame(['active', 'disabled'], $column->getEnumValues());
        $this->assertSame('disabled', $column->getDefaultValue());

        $this->assertFalse($column->isNullable());
    }

    /**
     * @expectedException \Spiral\ORM\Exception\Schema\DeclarationException
     */
    public function testRenderBadDeclaration()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => '~',
            ],
            []
        );
    }

    /**
     * @expectedException \Spiral\ORM\Exception\Schema\DeclarationException
     */
    public function testRenderBadDeclaration2()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => 'enum(a',
            ],
            []
        );
    }

    /**
     * @expectedException \Spiral\ORM\Exception\Schema\DeclarationException
     */
    public function testRenderBadDeclaration3()
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new ColumnRenderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => 'master',
            ],
            []
        );
    }

    /**
     * @param AbstractTable $table
     * @return AbstractTable
     */
    private function reload(AbstractTable $table): AbstractTable
    {
        $table->save();

        return $this->getDatabase()->table($table->getName())->getSchema();
    }
}