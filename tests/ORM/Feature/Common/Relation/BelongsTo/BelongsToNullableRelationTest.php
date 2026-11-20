<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\BelongsTo;

use Cycle\ORM\Options;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\Database\Assert\QueryAssert;

class BelongsToNullableRelationTest extends BelongsToRelationTest
{

    protected const NULLABLE = true;

    /**
     * Nullable BelongsTo relation should not be transformed to RefersTo
     */
    #[\Testo\Test]
    public function testSetNullable(): void
    {
        \Testo\Assert::true($this->orm
            ->getSchema()
            ->defineRelation(Profile::class, 'user')[Relation::SCHEMA][Relation::NULLABLE]);
        \Testo\Assert::instanceOf($this->orm->getRelationMap(Profile::class)->getRelations()['user'], Relation\BelongsTo::class);
    }

    /**
     * If parent relation is replaced with null - set
     */
    #[\Testo\Test]
    public function testRemoveParentUsingSetNull(): void
    {
        /** @var Profile $profile */
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)
            ->with('user')->fetchOne();

        \Testo\Assert::instanceOf($profile->user, User::class);
        QueryAssert::writes(0, fn() => $this->save($profile));

        $profile->user = null;
        QueryAssert::writes(1, fn() => $this->save($profile));
        QueryAssert::writes(0, fn() => $this->save($profile));

        $this->orm->getHeap()->clean();
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)
            ->load('user')
            ->fetchOne();
        \Testo\Assert::null($profile->user);
    }

    /**
     * If relation property was unset and IgnoreUninitializedRelations option is false - set to null
     */
    #[\Testo\Test]
    public function testUnsetPropertyWithoutIgnoreUninitializedRelations(): void
    {
        $this->orm = $this->orm->with(options: (new Options())->withIgnoreUninitializedRelations(false));
        /** @var Profile $profile */
        $profile = (new Select($this->orm, Profile::class))
            ->wherePK(1)->load('user')->fetchOne();

        unset($profile->user);
        QueryAssert::writes(1, fn() => $this->save($profile));
    }
}
