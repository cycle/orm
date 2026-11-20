<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Testing\Database\Schema\TableRenderer as Renderer;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class TableRendererTest extends OrmTestCase
{
    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::same($column->getDefaultValue(), 'default');
        Assert::false($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::same($column->getDefaultValue(), null);
        Assert::true($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::true($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::true($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::same($column->getEnumValues(), ['active', 'disabled']);
        Assert::same($column->getDefaultValue(), 'active');

        Assert::false($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::same($column->getEnumValues(), ['active', 'disabled']);
        Assert::same($column->getDefaultValue(), null);

        Assert::true($column->isNullable());
    }

    #[Test]
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

        Assert::true($table->hasColumn('name'));
        $column = $table->column('name');

        Assert::same($column->getType(), 'string');
        Assert::same($column->getEnumValues(), ['active', 'disabled']);
        Assert::same($column->getDefaultValue(), 'disabled');

        Assert::false($column->isNullable());
    }

    #[Test]
    public function testRenderBadDeclaration(): void
    {
        Expect::exception(SchemaException::class);

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

    #[Test]
    public function testRenderBadDeclaration2(): void
    {
        Expect::exception(SchemaException::class);

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

    #[Test]
    public function testRenderBadDeclaration3(): void
    {
        Expect::exception(SchemaException::class);

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
