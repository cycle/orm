<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\PostTag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7\Entity\Tag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * ManyToMany load=eager MUST load related entities.
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testCreatePostEntity(): void
    {
        // save post
        $post = new Post('title3', 'content3');
        (new EntityManager($this->orm))->persist($post)->run();

        // save post tag
        $pt1 = new PostTag();
        $pt1->post = $post;
        $pt1->tag = $this->orm->get(Tag::class, ['id' => 1]);
        (new EntityManager($this->orm))->persist($pt1)->run();

        $id = $post->id;
        unset($post);

        $post = $this->orm->getRepository(Post::class)->findByPK($id);
        self::assertCount(1, $post->postTags);
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
                'id' => 'primary',
                'post_id' => 'int',
                'tag_id' => 'int',
            ],
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
