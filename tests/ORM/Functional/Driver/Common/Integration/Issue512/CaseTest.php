<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue512;

use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSelect(): void
    {
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
            'content' => 'string',
        ]);

        $this->makeTable('tag', [
            'id' => 'primary',
            'label' => 'string',
        ]);

        $this->makeTable(
            table: 'post_tag',
            columns: [
                'post_id' => 'int',
                'tag_id' => 'int',
            ],
            pk: ['post_id', 'tag_id']
        );
        $this->makeFK('post_tag', 'post_id', 'post', 'id', 'NO ACTION', 'CASCADE');
        $this->makeFK('post_tag', 'tag_id', 'tag', 'id', 'NO ACTION', 'CASCADE');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('post')->insertMultiple(
            ['title', 'content'],
            [
                ['Title 1', '1 tag'],
                ['Title 2', '2 tags'],
            ],
        );
    }
}
