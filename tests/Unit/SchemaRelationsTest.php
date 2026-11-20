<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Exception\SchemaException;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(Schema::class)]
final class SchemaRelationsTest
{
    public function belongsToIsLinkedWithMatchingHasMany(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'ghost' => self::belongsTo('missing', 'ghost_id', 'id'),
                'posts' => self::hasMany('post', 'id', 'user_id', [Relation::INVERSION => 'author']),
                'chief' => self::belongsTo('manager', 'chief_id', 'id'),
                'stranger' => self::belongsTo('manager', 'other_id', ['id']),
                'filtered' => self::belongsTo('manager', 'manager_id', ['id'], [Relation::WHERE => ['a' => 1]]),
                'boss' => self::belongsTo('manager', 'manager_id', ['id']),
            ]],
            'post' => [SchemaInterface::RELATIONS => [
                'author' => self::belongsTo('user', 'user_id', 'id'),
            ]],
            'manager' => [SchemaInterface::RELATIONS => [
                'other' => self::hasMany('post', 'id', 'manager_id'),
                'mirror' => self::belongsTo('user', 'id', 'manager_id'),
                'staff' => self::hasMany('user', 'id', ['manager_id']),
            ]],
        ]);

        Assert::same(self::inversion($schema, 'post', 'author'), 'posts');
        Assert::same(self::inversion($schema, 'user', 'boss'), 'staff');
        Assert::same(self::inversion($schema, 'manager', 'staff'), 'boss');
        Assert::null(self::inversion($schema, 'user', 'stranger'));
        Assert::null(self::inversion($schema, 'user', 'filtered'));
        Assert::null(self::inversion($schema, 'manager', 'mirror'));
        Assert::null(self::inversion($schema, 'user', 'ghost'));
    }

    public function belongsToKeysCompareAsLists(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'team' => self::belongsTo('team', ['team_id'], 'id'),
                'owner' => self::belongsTo('team', 'owner_id', 'id'),
            ]],
            'team' => [SchemaInterface::RELATIONS => [
                'members' => self::hasMany('user', ['id'], 'team_id'),
                'owners' => self::hasMany('user', 'id', 'name'),
            ]],
        ]);

        Assert::same(self::inversion($schema, 'user', 'team'), 'members');
        Assert::null(self::inversion($schema, 'user', 'owner'));
    }

    public function belongsToIsNotLinkedWhenTargetWhereIsSet(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'team' => self::belongsTo('team', 'team_id', 'id'),
            ]],
            'team' => [SchemaInterface::RELATIONS => [
                'members' => self::hasMany('user', 'id', 'team_id', [Relation::WHERE => ['a' => 1]]),
            ]],
        ]);

        Assert::null(self::inversion($schema, 'user', 'team'));
    }

    public function explicitInversionOnTargetSideWins(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'mentor' => self::belongsTo('manager', 'mentor_id', 'id'),
                'boss' => self::belongsTo('manager', 'manager_id', 'id'),
            ]],
            'manager' => [SchemaInterface::RELATIONS => [
                'mentees' => self::hasMany('user', 'id', 'mentor_id', [Relation::INVERSION => 'mentor']),
                'staff' => self::hasMany('user', 'id', 'manager_id', [Relation::INVERSION => 'boss']),
                'copy' => self::hasMany('user', 'id', 'manager_id'),
            ]],
        ]);

        Assert::same(self::inversion($schema, 'user', 'mentor'), 'mentees');
        Assert::same(self::inversion($schema, 'user', 'boss'), 'staff');
        Assert::null(self::inversion($schema, 'manager', 'copy'));
    }

    public function laterRelationIsLinkedAfterForeignInversion(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'mentor' => self::belongsTo('manager', 'mentor_id', 'id'),
                'boss' => self::belongsTo('manager', 'manager_id', 'id'),
            ]],
            'manager' => [SchemaInterface::RELATIONS => [
                'mentees' => self::hasMany('user', 'id', 'mentor_id', [Relation::INVERSION => 'mentor']),
                'staff' => self::hasMany('user', 'id', 'manager_id'),
            ]],
        ]);

        Assert::same(self::inversion($schema, 'user', 'boss'), 'staff');
    }

    public function inversionsWithDifferentValuesAreRejected(): never
    {
        Expect::exception(SchemaException::class)
            ->withMessage("Relation `user.posts` can't be inversion of `post.author` because they have different relation values.");

        new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'posts' => self::hasMany('post', 'id', 'user_id', [Relation::INVERSION => 'author']),
                'comments' => self::hasMany('post', 'id', 'user_id'),
            ]],
            'post' => [SchemaInterface::RELATIONS => [
                'author' => self::belongsTo('user', 'user_id', 'id', [Relation::INVERSION => 'comments']),
            ]],
        ]);
    }

    public function manyToManyIsLinkedWithSymmetricManyToMany(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'tags' => self::mtm('tag', 'id', 'id', [
                    Relation::WHERE => ['a' => 1],
                    Relation::THROUGH_WHERE => ['b' => 2],
                ]),
            ]],
            'tag' => [SchemaInterface::RELATIONS => [
                'users' => self::mtm('user', 'id', 'id', [
                    Relation::WHERE => ['a' => 1],
                    Relation::THROUGH_WHERE => ['b' => 2],
                ]),
            ]],
            'pivot' => [],
        ]);

        Assert::same(self::inversion($schema, 'user', 'tags'), 'users');
        Assert::same(self::inversion($schema, 'tag', 'users'), 'tags');
    }

    public function manyToManyWithDifferentKeysOrFiltersIsNotLinked(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'keys' => self::mtm('k', 'id', 'id'),
                'where' => self::mtm('w', 'id', 'id', [Relation::WHERE => ['a' => 1]]),
                'through' => self::mtm('t', 'id', 'id', [Relation::THROUGH_WHERE => ['b' => 2]]),
            ]],
            'k' => [SchemaInterface::RELATIONS => ['users' => self::mtm('user', 'id', 'uuid')]],
            'w' => [SchemaInterface::RELATIONS => ['users' => self::mtm('user', 'id', 'id')]],
            't' => [SchemaInterface::RELATIONS => ['users' => self::mtm('user', 'id', 'id')]],
            'pivot' => [],
        ]);

        Assert::null(self::inversion($schema, 'user', 'keys'));
        Assert::null(self::inversion($schema, 'user', 'where'));
        Assert::null(self::inversion($schema, 'user', 'through'));
    }

    public function outerRelationsIncludePivotLinks(): void
    {
        $tags = self::mtm('tag', 'id', 'uuid', [Relation::CASCADE => true]);
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'tags' => $tags,
                'groups' => self::mtm('group', 'id', 'id', [Relation::INVERSION => 'members']),
            ]],
            'tag' => [],
            'group' => [SchemaInterface::RELATIONS => ['members' => self::mtm('user', 'id', 'id')]],
            'pivot' => [],
        ]);

        $outer = $schema->getOuterRelations('pivot');

        Assert::same($outer['user']['tags'], [
            Relation::TYPE => Relation::HAS_MANY,
            Relation::TARGET => 'pivot',
            Relation::SCHEMA => [
                Relation::CASCADE => true,
                Relation::INNER_KEY => 'id',
                Relation::OUTER_KEY => 'user_id',
            ],
        ]);
        Assert::same($outer['tag']['user.tags:tag'], [
            Relation::TYPE => Relation::HAS_MANY,
            Relation::TARGET => 'pivot',
            Relation::SCHEMA => [
                Relation::CASCADE => true,
                Relation::INNER_KEY => 'uuid',
                Relation::OUTER_KEY => 'target_id',
            ],
        ]);
        Assert::same(\array_keys($outer['group']), ['members']);
    }

    public function outerRelationsSkipForeignPivots(): void
    {
        $schema = new Schema([
            'user' => [SchemaInterface::RELATIONS => [
                'tags' => self::mtm('tag', 'id', 'id'),
                'profile' => self::belongsTo('profile', 'profile_id', 'id'),
            ]],
            'tag' => [],
            'profile' => [],
            'pivot' => [],
        ]);

        $outer = $schema->getOuterRelations('profile');

        Assert::same(\array_keys($outer), ['user']);
        Assert::same(\array_keys($outer['user']), ['profile']);
    }

    public function nonClassEntityIsNotAnAlias(): void
    {
        $schema = new Schema(['user' => [SchemaInterface::ENTITY => 'not-a-class']]);

        Assert::false($schema->defines('not-a-class'));
        Assert::same($schema->define('user', SchemaInterface::ENTITY), 'not-a-class');
    }

    public function childDeclaredInTwoRolesBelongsToTheLast(): void
    {
        $schema = new Schema([
            'a' => [SchemaInterface::CHILDREN => ['x' => 'x']],
            'b' => [SchemaInterface::CHILDREN => ['x' => 'x']],
        ]);

        Assert::same($schema->resolveAlias('x'), 'b');
        Assert::same($schema->resolveAlias('a'), 'a');
    }

    public function inheritedRolesKeepGrandchildrenDeclaredFirst(): void
    {
        $schema = new Schema([
            'c' => [SchemaInterface::PARENT => 'b'],
            'b' => [SchemaInterface::PARENT => 'a'],
            'a' => [],
        ]);

        Assert::same($schema->getInheritedRoles('b'), ['c' => []]);
        Assert::same($schema->getInheritedRoles('a'), ['b' => ['c' => []]]);
    }

    private static function inversion(Schema $schema, string $role, string $relation): ?string
    {
        return $schema->defineRelation($role, $relation)[Relation::SCHEMA][Relation::INVERSION] ?? null;
    }

    private static function belongsTo(string $target, string|array $inner, string|array $outer, array $extra = []): array
    {
        return self::relation(Relation::BELONGS_TO, $target, $inner, $outer, $extra);
    }

    private static function hasMany(string $target, string|array $inner, string|array $outer, array $extra = []): array
    {
        return self::relation(Relation::HAS_MANY, $target, $inner, $outer, $extra);
    }

    /**
     * Many-to-many through the `pivot` role: `user_id` refers to the source, `target_id` to the target.
     */
    private static function mtm(string $target, string $inner, string $outer, array $extra = []): array
    {
        return self::relation(Relation::MANY_TO_MANY, $target, $inner, $outer, $extra + [
            Relation::THROUGH_ENTITY => 'pivot',
            Relation::THROUGH_INNER_KEY => 'user_id',
            Relation::THROUGH_OUTER_KEY => 'target_id',
        ]);
    }

    private static function relation(int $type, string $target, string|array $inner, string|array $outer, array $extra): array
    {
        return [
            Relation::TYPE => $type,
            Relation::TARGET => $target,
            Relation::SCHEMA => [Relation::INNER_KEY => $inner, Relation::OUTER_KEY => $outer] + $extra,
        ];
    }
}
