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
         * @note When we create a new Post entity manually, ORM will not have magic abilities there.
         * @link https://spiral.dev/blog/cycle-orm-hungry-for-relations
         *
         * But there {@see Post::$postTags} is not initialized. ORM will ignore uninitialized relations.
         * That's why after eager loading below ORM will hydrate the empty relation.
         */
        $post = new Post('title3', 'content3');
        $this->save($post);

        /**
         * Right way
         *
         * @note We use ORM to create a new Post entity.
         *       We get an entity-proxy that has a promise in relation under the hood.
         */
        // $post = $this->orm->make(Post::class, ['title' => 'title3', 'content' => 'content3']);
        // $this->save($post);


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
         * @note When we use a repository to get the Post entity, we actually get the same cached entity from the heap.
         *       So, if the relation has an empty collection, it won't be overwritten.
         *       But we have uninitialized relation in the heap. So, ORM will hydrate it.
         *
         * @since ORM 2.10.0 EntityManager can fill only unresolved relations
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
