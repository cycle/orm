<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71;

use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\Entity\Tag;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * Loading a HasMany relation of entities that are loaded through a ManyToMany relation.
 *
 * @link https://github.com/cycle/orm/issues/71
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public static function loadMethodProvider(): iterable
    {
        $all = [
            'php' => [
                'post-1' => ['comment-1', 'comment-2'],
                'post-2' => [],
            ],
            'orm' => [
                'post-2' => [],
                'post-3' => ['comment-3'],
            ],
        ];

        // INNER JOIN on comments drops posts without comments
        yield 'JOIN' => [JoinableLoader::JOIN, [
            'php' => ['post-1' => ['comment-1', 'comment-2']],
            'orm' => ['post-3' => ['comment-3']],
        ]];
        yield 'LEFT_JOIN' => [JoinableLoader::LEFT_JOIN, $all];
        yield 'POSTLOAD' => [JoinableLoader::POSTLOAD, $all];
    }

    /**
     * @dataProvider loadMethodProvider
     */
    public function testFetchData(int $method, array $expected): void
    {
        $data = $this->select($method)->fetchData();

        $result = [];
        foreach ($data as $tag) {
            foreach ($tag['posts'] as $pivot) {
                $post = $pivot['@'];
                $result[$tag['name']][$post['title']] = \array_column($post['comments'], 'message');
            }
        }

        $this->assertSame($expected, $this->normalize($result));
    }

    /**
     * @dataProvider loadMethodProvider
     */
    public function testFetchAll(int $method, array $expected): void
    {
        $tags = $this->select($method)->fetchAll();

        $result = [];
        foreach ($tags as $tag) {
            foreach ($tag->posts as $post) {
                $result[$tag->name][$post->title] = \array_map(
                    static fn(Entity\Comment $comment): string => $comment->message,
                    $post->comments,
                );
            }
        }

        $this->assertSame($expected, $this->normalize($result));
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function select(int $method): Select
    {
        return (new Select($this->orm, Tag::class))
            ->load('posts', ['method' => $method])
            ->load('posts.comments', ['method' => $method])
            ->orderBy('id', 'DESC');
    }

    /**
     * Posts of a tag come in no guaranteed order.
     */
    private function normalize(array $result): array
    {
        foreach ($result as &$posts) {
            \ksort($posts);
        }

        return $result;
    }

    private function makeTables(): void
    {
        $this->makeTable('tag', [
            'id' => 'primary',
            'name' => 'string',
        ]);

        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
        ]);

        $this->makeTable('post_tag', [
            'id' => 'primary',
            'post_id' => 'int',
            'tag_id' => 'int',
        ]);
        $this->makeFK('post_tag', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('post_tag', 'tag_id', 'tag', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('comment', [
            'id' => 'primary',
            'post_id' => 'int,nullable',
            'message' => 'string',
        ]);
        $this->makeFK('comment', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('tag')->insertMultiple(['name'], [['orm'], ['php']]);
        $this->getDatabase()->table('post')->insertMultiple(['title'], [['post-1'], ['post-2'], ['post-3']]);
        $this->getDatabase()->table('post_tag')->insertMultiple(
            ['post_id', 'tag_id'],
            [[1, 2], [2, 2], [2, 1], [3, 1]],
        );
        $this->getDatabase()->table('comment')->insertMultiple(
            ['post_id', 'message'],
            [[1, 'comment-1'], [3, 'comment-3'], [1, 'comment-2']],
        );
    }
}
