<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7;

use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\PostTag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Tag;
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

    public function testSelect(): void
    {
        /** @var Post $post1 */
        $post1 = $this->orm->get(Post::class, ['id' => 1]);
        self::assertCount(1, $post1->tags);
        self::assertSame('foo', $post1->tags[0]->label);

        /** @var Post $post2 */
        $post2 = $this->orm->get(Post::class, ['id' => 2]);
        self::assertCount(2, $post2->tags);
        self::assertSame('bar', $post2->tags[0]->label);
        self::assertSame('baz', $post2->tags[1]->label);

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
                'id' => 'int',
                'post_id' => 'int',
                'tag_id' => 'int',
            ],
            pk: ['post_id', 'tag_id'],
        );
        $this->makeFK('post_tag', 'post_id', 'post', 'id', 'NO ACTION', 'CASCADE');
        $this->makeFK('post_tag', 'tag_id', 'tag', 'id', 'NO ACTION', 'CASCADE');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('post')->insertMultiple(
            ['id', 'title', 'content'],
            [
                [1, 'Title 1', '1 tag'],
                [2, 'Title 2', '2 tags'],
            ],
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['id', 'label'],
            [
                [1, 'foo'],
                [2, 'bar'],
                [3, 'baz'],
            ],
        );

        $this->getDatabase()->table('post_tag')->insertMultiple(
            ['id', 'post_id', 'tag_id'],
            [
                [11, 1, 1],
                [22, 2, 2],
                [23, 2, 3],
            ],
        );
    }
}
