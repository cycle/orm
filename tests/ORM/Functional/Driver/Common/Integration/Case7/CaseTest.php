<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case7;

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
        // Create and save post

        /**
         * Wrong way
         *
         * @note When we create a new Post entity manually, ORM has no ability to put a promise in relation.
         *       So, when we call $post->postTags, it will be empty.
         * @link https://spiral.dev/blog/cycle-orm-hungry-for-relations
         */
        // $post = new Post('title3', 'content3');
        // $this->save($post);

        /**
         * Right way
         *
         * @note We use ORM to create a new Post entity.
         *       We get an entity-proxy that has a promise in relation under the hood.
         */
        $post = $this->orm->make(Post::class, ['title' => 'title3', 'content' => 'content3']);


        // Get tag
        $tag = $this->orm->get(Tag::class, ['id' => 1]);
        self::assertSame('foo', $tag->label);

        // Save PostTag
        $pt1 = $this->orm->make(PostTag::class);
        $pt1->post = $post;
        $pt1->tag = $tag;
        $this->save($pt1);

        $id = $post->id;
        unset($post);

        /**
         * @note When we use repository to get a Post entity, we actually get the same cached entity from the heap.
         *       So, the empty relation won't be overwritten.
         *
         *       We can call `$this->orm->getHeap()->clean();` to clear the heap.
         */
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
        $this->getDatabase()->table('tag')->insertOne(['label' => 'foo']);
    }
}
