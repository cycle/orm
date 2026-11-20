<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\BelongsTo;

use Cycle\ORM\Options;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

final class BelongsToNullableRelationTest extends BelongsToRelationTest
{
    protected const NULLABLE = true;

    /**
     * Nullable BelongsTo relation should not be transformed to RefersTo
     */
    #[Test]
    public function testSetNullable(): void
    {
        Assert::true($this->orm
            ->getSchema()
            ->defineRelation(Profile::class, 'user')[Relation::SCHEMA][Relation::NULLABLE]);
        Assert::instanceOf($this->orm->getRelationMap(Profile::class)->getRelations()['user'], Relation\BelongsTo::class);
    }

    /**
     * If parent relation is replaced with null - set
     */
    #[Test]
    public function testRemoveParentUsingSetNull(): void
    {
        /** @var Profile $profile */
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)
            ->with('user')->fetchOne();

        Assert::instanceOf($profile->user, User::class);

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
        Assert::null($profile->user);
    }

    /**
     * If relation property was unset and IgnoreUninitializedRelations option is false - set to null
     */
    #[Test]
    public function testUnsetPropertyWithoutIgnoreUninitializedRelations(): void
    {
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
