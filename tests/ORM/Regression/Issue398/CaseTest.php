<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue398;

use Cycle\Database\Injection\Fragment;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Regression\Issue398\Entity\Product;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;

class CaseTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testSelectWithJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->join('inner', 'filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        \Testo\Assert::same($product->title, 'Product-2');
    }

    #[\Testo\Test]
    public function testSelectWithInnerJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->innerJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        \Testo\Assert::same($product->title, 'Product-2');
    }

    #[\Testo\Test]
    #[ExceptDriver(Driver::SQLite)]
    public function testSelectWithRightJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->rightJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        \Testo\Assert::same($product->title, 'Product-2');
    }

    #[\Testo\Test]
    public function testSelectWithLeftJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->leftJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        \Testo\Assert::same($product->title, 'Product-2');
    }

    #[\Testo\Test]
    #[ExceptDriver(Driver::SQLite, Driver::MySQL)]
    public function testSelectWithFullJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->fullJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        \Testo\Assert::same($product->title, 'Product-2');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable('products', [
            'id' => 'primary',
            'title' => 'string',
        ]);

        $this->makeTable('filter_products', [
            'filter_id' => 'int',
            'product_id' => 'int',
        ], pk: ['filter_id', 'product_id']);
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('products')->insertMultiple(
            ['title'],
            [['Product-1'], ['Product-2'], ['Product-3']],
        );

        $this->getDatabase()->table('filter_products')->insertMultiple(
            ['filter_id', 'product_id'],
            [[1, 1], [5, 2], [6, 3]],
        );
    }
}
