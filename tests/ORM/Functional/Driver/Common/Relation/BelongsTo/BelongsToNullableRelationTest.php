<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo;

use Cycle\ORM\Options;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class BelongsToNullableRelationTest extends BelongsToRelationTest
{
    use TableTrait;

    protected const NULLABLE = true;

    /**
     * Nullable BelongsTo relation should not be transformed to RefersTo
     */
    public function testSetNullable(): void
    {
        $this->assertTrue(
            $this->orm
                ->getSchema()
                ->defineRelation(Profile::class, 'user')[Relation::SCHEMA][Relation::NULLABLE],
        );
        $this->assertInstanceOf(
            Relation\BelongsTo::class,
            $this->orm->getRelationMap(Profile::class)->getRelations()['user'],
        );
    }

    /**
     * If parent relation is replaced with null - set
     */
    public function testRemoveParentUsingSetNull(): void
    {
        /** @var Profile $profile */
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)
            ->with('user')->fetchOne();

        $this->assertInstanceOf(User::class, $profile->user);

        $this->captureWriteQueries();
        $this->save($profile);
        $this->assertNumWrites(0);

        $profile->user = null;

        $this->captureWriteQueries();
        $this->save($profile);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($profile);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)
            ->load('user')
            ->fetchOne();
        $this->assertNull($profile->user);
    }

    /**
     * If relation property was unset and IgnoreUninitializedRelations option is false - set to null
     */
    public function testUnsetPropertyWithoutIgnoreUninitializedRelations(): void
    {
        echo static::class;
        $this->orm = $this->orm->with(options: (new Options())->withIgnoreUninitializedRelations(false));
        /** @var Profile $profile */
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)->load('user')->fetchOne();

        unset($profile->user);

        $this->captureWriteQueries();
        $this->save($profile);
        $this->assertNumWrites(1);
    }
}
