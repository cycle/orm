<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class JsonMethodsTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testWhereJson(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJson('settings->theme', 'light')->fetchOne();

        \Testo\Assert::same($user->id, 2);
        \Testo\Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testWhereJsonWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJson('user.settings->theme', 'light')->fetchOne();

        \Testo\Assert::same($post->id, 2);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testOrWhereJson(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJson('settings->theme', 'light')
            ->fetchOne();

        \Testo\Assert::same($user->id, 2);
        \Testo\Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testOrWhereJsonWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJson('user.settings->theme', 'light')
            ->fetchOne();

        \Testo\Assert::same($post->id, 2);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testWhereJsonContains(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonContains('settings->foo', ['bar', 'baz'])->fetchAll();

        \Testo\Assert::same($users[0]->id, 1);
        \Testo\Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonContainsWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $posts = $selector->whereJsonContains('user.settings->foo', ['bar', 'baz'])->fetchAll();

        \Testo\Assert::same($posts[0]->id, 1);
        \Testo\Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonContains(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector
            ->where('id', 100)
            ->orWhereJsonContains('user.settings->foo', ['bar', 'baz'])
            ->fetchAll();

        \Testo\Assert::same($users[0]->id, 1);
        \Testo\Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonContainsWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $posts = $selector
            ->where('id', 100)
            ->orWhereJsonContains('user.settings->foo', ['bar', 'baz'])
            ->fetchAll();

        \Testo\Assert::same($posts[0]->id, 1);
        \Testo\Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContain(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonDoesntContain('settings->theme', 'light')->fetchAll();

        \Testo\Assert::same($users[0]->id, 1);
        \Testo\Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContainWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $posts = $selector->whereJsonDoesntContain('user.settings->theme', 'light')->fetchAll();

        \Testo\Assert::same($posts[0]->id, 1);
        \Testo\Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContain(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContain('settings->theme', 'light')
            ->fetchAll();

        \Testo\Assert::same($users[0]->id, 1);
        \Testo\Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContainWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $posts = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContain('user.settings->theme', 'light')
            ->fetchAll();

        \Testo\Assert::same($posts[0]->id, 1);
        \Testo\Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonContainsKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJsonContainsKey('settings->foo')->fetchOne();

        \Testo\Assert::same($user->id, 1);
        \Testo\Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonContainsKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonContainsKey('user.settings->foo')->fetchOne();

        \Testo\Assert::same($post->id, 1);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonContainsKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonContainsKey('settings->foo')
            ->fetchOne();

        \Testo\Assert::same($user->id, 1);
        \Testo\Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonContainsKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonContainsKey('user.settings->foo')
            ->fetchOne();

        \Testo\Assert::same($post->id, 1);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContainKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJsonDoesntContainKey('settings->foo')->fetchOne();

        \Testo\Assert::same($user->id, 2);
        \Testo\Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContainKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonDoesntContainKey('user.settings->foo')->fetchOne();

        \Testo\Assert::same($post->id, 2);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContainKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContainKey('settings->foo')
            ->fetchOne();

        \Testo\Assert::same($user->id, 2);
        \Testo\Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContainKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContainKey('user.settings->foo')
            ->fetchOne();

        \Testo\Assert::same($post->id, 2);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testWhereJsonLength(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJsonLength('settings->foo', 2)->fetchOne();

        \Testo\Assert::same($user->id, 1);
        \Testo\Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testWhereJsonLengthWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonLength('user.settings->foo', 2)->fetchOne();

        \Testo\Assert::same($post->id, 1);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonLength(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonLength('settings->foo', 2)
            ->fetchOne();

        \Testo\Assert::same($user->id, 1);
        \Testo\Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Test]
    public function testOrWhereJsonLengthWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonLength('user.settings->foo', 2)
            ->fetchOne();

        \Testo\Assert::same($post->id, 1);
        \Testo\Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('users', ['id' => 'primary', 'user_settings' => 'json,nullable']);
        $this->makeTable('posts', ['id' => 'primary', 'title' => 'string', 'user_id' => 'integer']);

        $this->getDatabase()->table('users')->insertMultiple(
            ['user_settings'],
            [[\json_encode(['theme' => 'dark', 'foo' => ['bar', 'baz']])], [\json_encode(['theme' => 'light'])]],
        );
        $this->getDatabase()->table('posts')->insertMultiple(['title', 'user_id'], [['Post 1', 1], ['Post 2', 2]]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'users',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id', 'settings' => 'user_settings'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => ['id' => 'int', 'settings' => 'json'],
                Schema::RELATIONS => [],
            ],
            Post::class => [
                Schema::ROLE => 'post',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'posts',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'title', 'user_id'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => 'user',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }
}
