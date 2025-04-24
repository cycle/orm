<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7;

use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * ManyToMany load=eager MUST load related entities.
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testGet(): void
    {
        /** @var Post $post1 */
        $post1 = $this->orm->get(Post::class, ['title' => 'Title1']);
        self::assertCount(1, $post1->tags);
        self::assertSame('foo', $post1->tags[0]->label);

        /** @var Post $post2 */
        $post2 = $this->orm->get(Post::class, ['title' => 'Title2']);
        self::assertCount(2, $post2->tags);
        self::assertSame('bar', $post2->tags[0]->label);
        self::assertSame('baz', $post2->tags[1]->label);
    }

    public function testRepositorySelect(): void
    {
        /** @var Post $post1 */
        $post1 = $this->orm->getRepository(Post::class)->findByPK(1);
        self::assertCount(1, $post1->tags);
        self::assertSame('foo', $post1->tags[0]->label);

        /** @var Post $post2 */
        $post2 = $this->orm->getRepository(Post::class)->findByPK(2);
        self::assertCount(2, $post2->tags);
        self::assertSame('bar', $post2->tags[0]->label);
        self::assertSame('baz', $post2->tags[1]->label);
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
        $p1 = $this->getDatabase()->table('post')->insertOne(['title' => 'Title1', 'content' => '1 tag']);
        $p2 = $this->getDatabase()->table('post')->insertOne(['title' => 'Title2', 'content' => '2 tag']);

        $t1 = $this->getDatabase()->table('tag')->insertOne(['label' => 'foo']);
        $t2 = $this->getDatabase()->table('tag')->insertOne(['label' => 'bar']);
        $t3 = $this->getDatabase()->table('tag')->insertOne(['label' => 'baz']);

        $this->getDatabase()->table('post_tag')->insertMultiple(
            ['post_id', 'tag_id'],
            [
                [$p1, $t1],
                [$p2, $t2],
                [$p2, $t3],
            ],
        );
    }
}
