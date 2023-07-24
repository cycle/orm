<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue425;

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
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSave(): void
    {
        $comment = new Entity\Comment();
        $comment->content = 'Comment 3';
        $comment->post_id = 1;

        $this->captureReadQueries();
        $this->captureWriteQueries();

        $this->save($comment);

        $this->assertNumReads(0);
        $this->assertNumWrites(1);
    }

    private function makeTables(): void
    {
        $this->makeTable('posts', [
            'id' => 'primary',
            'title' => 'string',
        ]);

        $this->makeTable('comments', [
            'id' => 'primary',
            'content' => 'string',
            'post_id' => 'integer',
        ]);
        $this->makeFK('comments', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('comments')->insertMultiple(
            ['content', 'post_id'],
            [
                ['Comment 1', 1],
                ['Comment 2', 1],
            ],
        );
        $this->getDatabase()->table('posts')->insertOne(['title' => 'Title 1']);
    }
}
