<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Loader\EmbeddedLoader;
use Cycle\ORM\Select\Loader\ManyToManyLoader;
use Cycle\ORM\Select\Loader\Morphed\BelongsToMorphedLoader;
use Cycle\ORM\Select\Loader\PivotLoader;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Select\UpdateLoader;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\Testing\Database\Layout;
use Cycle\Testing\ORM\Memory;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * Builds selects over an in-memory ORM and checks the SQL and loaders they produce; no row is fetched.
 */
#[Test]
final class SelectBuilderTest
{
    public function distinctSelectsUniqueRows(): void
    {
        $sql = $this->select()->distinct()->sqlStatement();

        Assert::string($sql)->contains('SELECT DISTINCT');
    }

    public function andWhereAddsAConjunction(): void
    {
        $sql = $this->select()->where('email', 'a')->andWhere('balance', 10)->sqlStatement();

        Assert::string($sql)->contains('"user"."email" = ? AND "user"."balance" = ?');
    }

    public function havingClausesAreCombined(): void
    {
        $sql = $this->select()
            ->groupBy('email')
            ->having('balance', '>', 1)
            ->andHaving('balance', '<', 9)
            ->orHaving('balance', 100)
            ->sqlStatement();

        Assert::string($sql)->contains(
            'HAVING "user"."balance" > ? AND "user"."balance" < ? OR "user"."balance" = ?',
        );
    }

    public function jsonContainmentFiltersCompileForTheDriver(): void
    {
        $sql = $this->select($this->mysqlOrm())
            ->whereJsonContains('email->tags', 'php')
            ->orWhereJsonContains('email->tags', 'go')
            ->whereJsonDoesntContain('email->tags', 'perl')
            ->orWhereJsonDoesntContain('email->tags', 'cobol')
            ->sqlStatement();

        Assert::string($sql)->contains(
            "WHERE json_contains(`user`.`email`, ?, '$.\"tags\"')"
            . " OR json_contains(`user`.`email`, ?, '$.\"tags\"')"
            . " AND NOT json_contains(`user`.`email`, ?, '$.\"tags\"')"
            . " OR NOT json_contains(`user`.`email`, ?, '$.\"tags\"')",
        );
    }

    public function loadAcceptsRelationsWithTheirOwnOptions(): void
    {
        $sql = $this->select()
            ->load(['comments' => ['method' => Select\JoinableLoader::JOIN], 'profile' => []])
            ->sqlStatement();

        Assert::string($sql)
            ->contains('INNER JOIN "comment" AS "l_user_comments"')
            ->contains('LEFT JOIN "profile" AS "l_user_profile"');
    }

    public function withJoinsListedRelations(): void
    {
        $sql = $this->select()
            ->with(['comments', 'profile' => ['as' => 'p']])
            ->sqlStatement();

        Assert::string($sql)
            ->contains('JOIN "comment" AS "user_comments"')
            ->contains('JOIN "profile" AS "p"');
    }

    public function unknownCallsReachTheQueryAndReturnItsResult(): void
    {
        $select = $this->select()->limit(5);

        Assert::same($select->getLimit(), 5);
    }

    public function cursorRequiresThePrimaryKeyAmongTheColumns(): void
    {
        $orm = Memory::orm(new Schema([
            'user' => [
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['email', 'balance'],
                Schema::RELATIONS => [],
            ],
        ]));

        Expect::exception(\LogicException::class)
            ->withMessage('Cursor cannot locate primary key column `id` among root loader columns [email, balance].');

        (new Select($orm, 'user'))->cursor()->current();
    }

    public function unknownLoaderOptionIsRejected(): void
    {
        Expect::exception(LoaderException::class)->withMessageContaining('does not support option: color');

        $this->select()->load('comments', ['color' => 'red']);
    }

    public function chainThroughAnEmbeddedRelationIsRejected(): void
    {
        Expect::exception(LoaderException::class)
            ->withMessage(\sprintf('Loader `%s` does not support chain relation loading.', EmbeddedLoader::class));

        $this->select()->load('credentials.username');
    }

    public function joinAcrossDatabasesIsRejected(): void
    {
        $schema = $this->schema();
        $schema[Profile::class][Schema::DATABASE] = 'secondary';
        $orm = Memory::orm(new Schema($schema), Layout::prefixed(['secondary' => 'sec_']));

        Expect::exception(LoaderException::class)->withMessage('Unable to join tables located in different databases');

        (new Select($orm, User::class))->with('profile');
    }

    public function loaderWithoutContextHasNoAlias(): void
    {
        $orm = $this->orm();
        $loader = $orm->getFactory()->loader(
            $orm->getSchema(),
            $orm->getService(SourceProviderInterface::class),
            'user',
            'comments',
        );

        Expect::exception(LoaderException::class)->withMessage('Unable to resolve loader alias.');

        $loader->getAlias();
    }

    public function builderHandsOutACopyOfTheLoader(): void
    {
        $select = $this->select();

        $select->getBuilder()->getLoader()->loadRelation('comments', [], true);

        Assert::string($select->sqlStatement())->notContains('comment');
    }

    public function loaderCanSwitchItsSource(): void
    {
        $loader = $this->select()->getBuilder()->getLoader();
        $source = new Source($this->orm()->getSource('user')->getDatabase(), 'archive');

        $copy = $loader->withSource($source);

        Assert::same($copy->getSource(), $source);
        Assert::notSame($loader->getSource(), $source);
    }

    public function pivotLoaderIsReachedAndReconfiguredThroughTheManyToManyLoader(): void
    {
        $tags = $this->select()->getBuilder()->getLoader()->loadRelation('tags', [], true);
        Assert::instanceOf($tags, ManyToManyLoader::class);

        $pivot = $tags->loadRelation('@', new LoadOptions(scope: false));

        Assert::instanceOf($pivot, PivotLoader::class);
        Assert::same($pivot->getTable(), 'tag_user_map');
        Assert::same($tags->loadRelation('@.@', []), $pivot);
    }

    public function manyToManyJoinBuildsThePivotJoin(): void
    {
        $sql = $this->select()->with('tags')->sqlStatement();

        Assert::string($sql)
            ->contains('JOIN "tag_user_map" AS "user_tags_pivot"')
            ->contains('JOIN "tag" AS "user_tags"');
    }

    public function postloadedRelationsLeaveTheRootQueryAlone(): void
    {
        $sql = $this->select(null, Comment::class)
            ->load('user', ['method' => Select\JoinableLoader::POSTLOAD])
            ->sqlStatement();

        Assert::string($sql)->notContains('JOIN');
    }

    public function embeddedLoaderFollowsItsParent(): void
    {
        $loader = $this->select()->getBuilder()->getLoader()->loadRelation('credentials', [], true);
        Assert::instanceOf($loader, EmbeddedLoader::class);

        $loader->setSubclassesLoading(true);

        Assert::same($loader->getTarget(), 'user_credentials');
        Assert::same($loader->getAlias(), 'user');
        Assert::true($loader->isJoined());
        Assert::false($loader->isHierarchical());
    }

    public function morphedLoaderHasNoTargetUntilItLoadsARole(): void
    {
        $loader = $this->select(null, Image::class)->getBuilder()->getLoader()->loadRelation('parent', [], false, true);
        Assert::instanceOf($loader, BelongsToMorphedLoader::class);
        Assert::true($loader->isLoaded());

        Expect::exception(\RuntimeException::class)->withMessage('Target role is not defined yet.');

        $loader->getTarget();
    }

    public function updateLoaderIsAlwaysLoaded(): void
    {
        $orm = $this->orm();
        $loader = new UpdateLoader(
            $orm->getSchema(),
            $orm->getService(SourceProviderInterface::class),
            $orm->getFactory(),
            'user',
        );

        Assert::true($loader->isLoaded());
        Assert::same($loader->getAlias(), 'user');
    }

    public function repositorySelectsItsRole(): void
    {
        $sql = $this->orm()->getRepository(Comment::class)->select()->sqlStatement();

        Assert::string($sql)->contains('FROM "comment" AS "comment"');
    }

    private function select(?ORMInterface $orm = null, string $role = User::class): Select
    {
        return new Select($orm ?? $this->orm(), $role);
    }

    private function orm(): ORMInterface
    {
        return Memory::orm(new Schema($this->schema()));
    }

    /**
     * An ORM on a MySQL connection that is never opened: queries are only compiled.
     */
    private function mysqlOrm(): ORMInterface
    {
        $dbal = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'mysql']],
            'connections' => [
                'mysql' => new MySQLDriverConfig(
                    connection: new TcpConnectionConfig(database: 'orm', host: '127.0.0.1', port: 1),
                ),
            ],
        ]));

        return new ORM(new Factory($dbal), new Schema($this->schema()));
    }

    private function schema(): array
    {
        return [
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'tags' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => TagContext::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'tag_id',
                        ],
                    ],
                    'credentials' => [
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => UserCredentials::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ],
            ],
            UserCredentials::class => [
                Schema::ROLE => 'user_credentials',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id', 'username' => 'creds_username'],
                Schema::RELATIONS => [],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'image'],
                Schema::RELATIONS => [],
            ],
            Tag::class => [
                Schema::ROLE => 'tag',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name'],
                Schema::RELATIONS => [],
            ],
            TagContext::class => [
                Schema::ROLE => 'tag_context',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'tag_id', 'as'],
                Schema::RELATIONS => [],
            ],
            Image::class => [
                Schema::ROLE => 'image',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'image',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id', 'parentId' => 'parent_id', 'parentType' => 'parent_type'],
                Schema::RELATIONS => [
                    'parent' => [
                        Relation::TYPE => Relation::BELONGS_TO_MORPHED,
                        Relation::TARGET => 'imaged',
                        Relation::SCHEMA => [
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::OUTER_KEY => 'id',
                            Relation::INNER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
                        ],
                    ],
                ],
            ],
        ];
    }
}
