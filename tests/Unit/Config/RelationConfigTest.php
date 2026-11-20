<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Config;

use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Relation;
use Cycle\ORM\Select\Loader\Morphed\BelongsToMorphedLoader;
use Cycle\ORM\Select\Loader\Morphed\MorphedHasManyLoader;
use Cycle\ORM\Select\Loader\Morphed\MorphedHasOneLoader;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataSet;
use Testo\Test;

#[Test]
#[Covers(RelationConfig::class)]
final class RelationConfigTest
{
    #[DataSet([Relation::MORPHED_HAS_ONE, MorphedHasOneLoader::class])]
    #[DataSet([Relation::MORPHED_HAS_MANY, MorphedHasManyLoader::class])]
    #[DataSet([Relation::BELONGS_TO_MORPHED, BelongsToMorphedLoader::class])]
    #[DataSet([Relation::REFERS_TO_MORPHED, BelongsToMorphedLoader::class])]
    public function defaultConfigHasMorphedLoaders(int $type, string $loader): void
    {
        Assert::same(RelationConfig::getDefault()->getLoader($type)->alias, $loader);
    }
}
