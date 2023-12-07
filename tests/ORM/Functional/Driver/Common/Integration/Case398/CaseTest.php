<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case398;

use Cycle\Database\Injection\Fragment;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case398\Entity\Product;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelectWithJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->join('inner', 'filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        $this->assertSame('Product-2', $product->title);
    }

    public function testSelectWithInnerJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->innerJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        $this->assertSame('Product-2', $product->title);
    }

    public function testSelectWithRightJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->rightJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        $this->assertSame('Product-2', $product->title);
    }

    public function testSelectWithLeftJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->leftJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        $this->assertSame('Product-2', $product->title);
    }

    public function testSelectWithFullJoin(): void
    {
        $select = new Select($this->orm, Product::class);
        $product = $select
            ->fullJoin('filter_products', 'fp')->on('id', 'fp.product_id')
            ->where(new Fragment('fp.filter_id = ?', 5))
            ->fetchOne();

        $this->assertSame('Product-2', $product->title);
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
