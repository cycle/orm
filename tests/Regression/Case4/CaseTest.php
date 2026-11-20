<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Case4;

use Cycle\ORM\Select;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * @link https://github.com/cycle/orm/pull/414
 */
final class CaseTest extends OrmTestCase
{
    #[Test]
    public function testSelect(): void
    {
        $model = (new Select($this->orm, Entity\Node::class))
            ->wherePK(2)
            ->fetchOne();

        $this->save($model);

        $model = (new Select($this->orm, Entity\Node::class))
            ->wherePK(3)
            ->fetchOne();

        $this->save($model);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        // Init DB
        // Make tables
        $this->makeTable('nodes', [
            'id' => 'int,primary',
            'key' => 'string',
            'parent_id' => 'int,nullable',
        ]);

        $this->loadSchema(__DIR__ . '/schema.php');

        $this->getDatabase()->table('nodes')->insertMultiple(
            ['id', 'key', 'parent_id'],
            [
                [1, 'root', null],
                [2, 'level 1', 1],
                [3, 'level 2', 2],
            ],
        );
    }
}
