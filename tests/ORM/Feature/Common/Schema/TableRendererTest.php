<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Testing\Database\Schema\TableRenderer as Renderer;
use Cycle\Testing\ORM\OrmTestCase;

class TableRendererTest extends OrmTestCase
{
    #[\Testo\Test]
    public function testRenderString(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'string',
            ],
            [
                'name' => 'default',
            ],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getDefaultValue(), 'default');
        \Testo\Assert::false($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderStringNullDefault(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'string',
            ],
            [
                'name' => null,
            ],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getDefaultValue(), null);
        \Testo\Assert::true($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderStringNullDeclared(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'string,null',
            ],
            [],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::true($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderStringNullableDeclared(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'string,nullable',
            ],
            [],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::true($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderEnum(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'enum(active,disabled)',
            ],
            [],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getEnumValues(), ['active', 'disabled']);
        \Testo\Assert::same($column->getDefaultValue(), 'active');

        \Testo\Assert::false($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderEnumNullDefault(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'enum(active,disabled)',
            ],
            [
                'name' => null,
            ],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getEnumValues(), ['active', 'disabled']);
        \Testo\Assert::same($column->getDefaultValue(), null);

        \Testo\Assert::true($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderEnumNullSecond(): void
    {
        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'id' => 'primary',
                'name' => 'enum(active,disabled)',
            ],
            [
                'name' => 'disabled',
            ],
        );

        $table = $this->reload($table);

        \Testo\Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getEnumValues(), ['active', 'disabled']);
        \Testo\Assert::same($column->getDefaultValue(), 'disabled');

        \Testo\Assert::false($column->isNullable());
    }

    #[\Testo\Test]
    public function testRenderBadDeclaration(): void
    {
        \Testo\Expect::exception(SchemaException::class);

        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => '~',
            ],
            [],
        );
    }

    #[\Testo\Test]
    public function testRenderBadDeclaration2(): void
    {
        \Testo\Expect::exception(SchemaException::class);

        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => 'enum(a',
            ],
            [],
        );
    }

    #[\Testo\Test]
    public function testRenderBadDeclaration3(): void
    {
        \Testo\Expect::exception(SchemaException::class);

        $table = $this->getDatabase()->table('sample')->getSchema();
        $renderer = new Renderer();

        $renderer->renderColumns(
            $table,
            [
                'column' => 'master',
            ],
            [],
        );
    }

    private function reload(AbstractTable $table): AbstractTable
    {
        $table->save();

        return $this->getDatabase()->table($table->getName())->getSchema();
    }
}
