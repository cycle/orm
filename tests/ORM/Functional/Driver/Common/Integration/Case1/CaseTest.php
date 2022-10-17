<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1;

use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity\Comment;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity\Tag;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\Entity\User;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    /** @var User[] */
    private array $users = [];
    /** @var Tag[] */
    private array $tags = [];
    private int $iterator = 0;

    private ?float $startTime = null;
    private array $memories = [];

    public function setUp(): void
    {
        $this->resetState();

        // Init DB
        parent::setUp();

        // Make tables
        $this->makeTable('user', [
            'id' => 'primary',
            'login' => 'string',
            'password_hash' => 'string',
            'created_at' => 'datetime,nullable',
            'updated_at' => 'datetime,nullable',
        ]);

        $this->makeTable('post', [
            'id' => 'primary',
            'slug' => 'string',
            'title' => 'string',
            'public' => 'bool',
            'content' => 'string',
            'created_at' => 'datetime,nullable',
            'updated_at' => 'datetime,nullable',
            'published_at' => 'datetime,nullable',
            'deleted_at' => 'datetime,nullable',
            'user_id' => 'int',
        ]);
        $this->makeFK('post', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('comment', [
            'id' => 'primary',
            'public' => 'bool',
            'content' => 'string',
            'created_at' => 'datetime,nullable',
            'updated_at' => 'datetime,nullable',
            'published_at' => 'datetime,nullable',
            'deleted_at' => 'datetime,nullable',
            'user_id' => 'int',
            'post_id' => 'int',
        ]);
        $this->makeFK('comment', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('comment', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('tag', [
            'id' => 'primary',
            'label' => 'string',
            'created_at' => 'datetime,nullable',
        ]);

        $this->makeTable('post_tag', [
            'id' => 'primary',
            'post_id' => 'int',
            'tag_id' => 'int',
        ]);
        $this->makeFK('post_tag', 'post_id', 'post', 'id', 'NO ACTION', 'CASCADE');
        $this->makeFK('post_tag', 'tag_id', 'tag', 'id', 'NO ACTION', 'CASCADE');

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function countProvider(): array
    {
        return [
            '1 item' => [1],
            '2 items' => [2],
            '10 items' => [10],
            '20 items' => [20],
            // '50 items' => [50],
        ];
    }

    /**
     * @dataProvider countProvider
     */
    public function testRun(int $count): void
    {
        $generator = $this->generate($count);

        while ($generator->valid()) {
            $this->snapMemory($generator->current());
            $generator->next();
        }

        // Reset state
        $this->resetState(false);
        $this->orm->getHeap()->clean();
        $this->snapMemory('Clean state');

        // No errors
        $this->assertTrue(true);

        // $this->printDebug();
    }

    /**
     * @return \Generator<array-key, string>
     */
    private function generate(int $count): \Generator
    {
        \assert($count > 0);
        yield 'Before generation';

        $this->addUsers($count);
        $this->addTags($count);
        $this->addPosts($count);

        yield 'Before transaction';

        $this->save(...$this->users);

        yield 'After transaction';
    }

    private function snapMemory(string $text): void
    {
        $this->startTime ??= microtime(true);
        $this->memories[$text] = [
            sprintf('%3.2fs', microtime(true) - $this->startTime),
            sprintf('%3.2fMiB', memory_get_usage(false) / (1204 * 1024)),
            sprintf('%3.2fMiB', memory_get_peak_usage(false) / (1204 * 1024)),
        ];
    }

    private function printDebug(): void
    {
        echo "Time :: Usage :: Peak\n";
        foreach ($this->memories as $text => $values) {
            echo "{$text}: \n " . implode(' ', $values) . "\n";
        }
    }

    private function addUsers(int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $login = 'Login' . ++$this->iterator;
            $user = new User($login, $login);
            $this->users[] = $user;
        }
    }

    private function addTags(int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $word = 'Tag' . ++$this->iterator;
            $tag = new Tag($word);
            $this->tags[] = $tag;
        }
    }

    private function addPosts(int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            /** @var User $postUser */
            $postUser = $this->users[array_rand($this->users)];
            $post = new Post('Post title ' . $this->iterator, 'Post big real test ' . ++$this->iterator);
            $postUser->addPost($post);
            $public = ($this->iterator % 2) === 1;
            $post->setPublic($public);
            if ($public) {
                $post->setPublishedAt(new \DateTimeImmutable(date('r', random_int(strtotime('-2 years'), time()))));
            }
            // link tags
            $postTags = (array)array_rand($this->tags, random_int(1, count($this->tags)));
            foreach ($postTags as $tagId) {
                $tag = $this->tags[$tagId];
                $post->addTag($tag);
                $tag->addPost($post);
            }
            // add comments
            $commentsCount = max(2, $count);
            for ($j = 0; $j <= $commentsCount; ++$j) {
                $comment = new Comment('Comment text ' . ++$this->iterator);
                $commentPublic = ($this->iterator % 3) === 0;
                $comment->setPublic($commentPublic);
                if ($commentPublic) {
                    $comment->setPublishedAt(new \DateTimeImmutable(date('r', random_int(strtotime('-1 years'), time()))));
                }
                $commentUser = $this->users[array_rand($this->users)];
                $commentUser->addComment($comment);
                $comment->setPost($post);
            }
        }
    }

    private function resetState(bool $resetMemSnap = true): void
    {
        $this->tags = [];
        $this->users = [];
        $this->iterator = 0;
        if ($resetMemSnap) {
            $this->startTime = null;
            $this->memories = [];
        }
    }
}
