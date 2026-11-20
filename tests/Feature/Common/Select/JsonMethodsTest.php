<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\Database\Exception\DriverException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\Database\Driver;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class JsonMethodsTest extends OrmTestCase
{
    #[Test]
    public function testWhereJson(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJson('settings->theme', 'light')->fetchOne();

        Assert::same($user->id, 2);
        Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testWhereJsonWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJson('user.settings->theme', 'light')->fetchOne();

        Assert::same($post->id, 2);
        Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testOrWhereJson(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJson('settings->theme', 'light')
            ->fetchOne();

        Assert::same($user->id, 2);
        Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testOrWhereJsonWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJson('user.settings->theme', 'light')
            ->fetchOne();

        Assert::same($post->id, 2);
        Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testWhereJsonContains(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonContains('settings->foo', ['bar', 'baz'])->fetchAll();

        Assert::same($users[0]->id, 1);
        Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonContainsWithRelation(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, Post::class);
        $posts = $selector->whereJsonContains('user.settings->foo', ['bar', 'baz'])->fetchAll();

        Assert::same($posts[0]->id, 1);
        Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonContains(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, User::class);
        $users = $selector
            ->where('id', 100)
            ->orWhereJsonContains('user.settings->foo', ['bar', 'baz'])
            ->fetchAll();

        Assert::same($users[0]->id, 1);
        Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonContainsWithRelation(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, Post::class);
        $posts = $selector
            ->where('id', 100)
            ->orWhereJsonContains('user.settings->foo', ['bar', 'baz'])
            ->fetchAll();

        Assert::same($posts[0]->id, 1);
        Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonDoesntContain(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonDoesntContain('settings->theme', 'light')->fetchAll();

        Assert::same($users[0]->id, 1);
        Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonDoesntContainWithRelation(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, Post::class);
        $posts = $selector->whereJsonDoesntContain('user.settings->theme', 'light')->fetchAll();

        Assert::same($posts[0]->id, 1);
        Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonDoesntContain(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, User::class);
        $users = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContain('settings->theme', 'light')
            ->fetchAll();

        Assert::same($users[0]->id, 1);
        Assert::equals($users[0]->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonDoesntContainWithRelation(): void
    {
        $this->expectDriverExceptionOnSQLite();

        $selector = new Select($this->orm, Post::class);
        $posts = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContain('user.settings->theme', 'light')
            ->fetchAll();

        Assert::same($posts[0]->id, 1);
        Assert::equals($posts[0]->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonContainsKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonContainsKey('settings->foo')->fetchAll();

        Assert::count($users, 1);
        $user = $users[0];
        Assert::same($user->id, 1);
        Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonContainsKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonContainsKey('user.settings->foo')->fetchOne();

        Assert::same($post->id, 1);
        Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonContainsKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonContainsKey('settings->foo')
            ->fetchOne();

        Assert::same($user->id, 1);
        Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonContainsKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonContainsKey('user.settings->foo')
            ->fetchOne();

        Assert::same($post->id, 1);
        Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonDoesntContainKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector->whereJsonDoesntContainKey('settings->foo')->fetchOne();

        Assert::same($user->id, 2);
        Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testWhereJsonDoesntContainKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonDoesntContainKey('user.settings->foo')->fetchOne();

        Assert::same($post->id, 2);
        Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testOrWhereJsonDoesntContainKey(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContainKey('settings->foo')
            ->fetchOne();

        Assert::same($user->id, 2);
        Assert::equals($user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testOrWhereJsonDoesntContainKeyWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonDoesntContainKey('user.settings->foo')
            ->fetchOne();

        Assert::same($post->id, 2);
        Assert::equals($post->user->settings, ['theme' => 'light']);
    }

    #[Test]
    public function testWhereJsonLength(): void
    {
        $selector = new Select($this->orm, User::class);
        $users = $selector->whereJsonLength('settings->foo', 2)->fetchAll();

        Assert::count($users, 1);
        $user = $users[0];
        Assert::same($user->id, 1);
        Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testWhereJsonLengthWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector->whereJsonLength('user.settings->foo', 2)->fetchOne();

        Assert::same($post->id, 1);
        Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonLength(): void
    {
        $selector = new Select($this->orm, User::class);
        $user = $selector
            ->where('id', 100)
            ->orWhereJsonLength('settings->foo', 2)
            ->fetchOne();

        Assert::same($user->id, 1);
        Assert::equals($user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[Test]
    public function testOrWhereJsonLengthWithRelation(): void
    {
        $selector = new Select($this->orm, Post::class);
        $post = $selector
            ->where('id', 100)
            ->orWhereJsonLength('user.settings->foo', 2)
            ->fetchOne();

        Assert::same($post->id, 1);
        Assert::equals($post->user->settings, ['theme' => 'dark', 'foo' => ['bar', 'baz']]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
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

    /**
     * SQLite has no JSON containment functions: the query fails with a driver exception there.
     */
    private function expectDriverExceptionOnSQLite(): void
    {
        $this->driver === Driver::SQLite and Expect::exception(DriverException::class);
    }
}
