<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case4;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();

        // Make tables
        $this->makeTable('nodes', [
            'id' => 'primary',
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

    public function testSelect(): void
    {
        $model = (new Select($this->orm, Entity\Node::class))
            ->wherePK(2)
            ->fetchOne();

        $manager = new EntityManager($this->orm);
        $manager->persist($model);
        $manager->run();

        $model = (new Select($this->orm, Entity\Node::class))
            ->wherePK(3)
            ->fetchOne();

        $manager->persist($model);

        $manager->run();
    }
}
