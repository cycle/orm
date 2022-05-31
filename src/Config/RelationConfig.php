<?php

declare(strict_types=1);

namespace Cycle\ORM\Config;

use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use JetBrains\PhpStorm\Pure;
use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;

final class RelationConfig extends InjectableConfig
{
    public const LOADER = 'loader';
    public const RELATION = 'relation';
    public const SCHEMA = 'schema';

    public function getLoader(int|string $type): Autowire
    {
        if (!isset($this->config[$type][self::LOADER])) {
            throw new ConfigException("Unable to get relation loader `{$type}`.");
        }

        return new Autowire($this->config[$type][self::LOADER]);
    }

    public function getRelation(int|string $type): Autowire
    {
        if (!isset($this->config[$type][self::RELATION])) {
            throw new ConfigException("Unable to get relation `{$type}`.");
        }

        return new Autowire($this->config[$type][self::RELATION]);
    }

    #[Pure]
    public static function getDefault(): self
    {
        return new self([
            Relation::EMBEDDED => [
                self::LOADER => Select\Loader\EmbeddedLoader::class,
                self::RELATION => Relation\Embedded::class,
            ],
            Relation::HAS_ONE => [
                self::LOADER => Select\Loader\HasOneLoader::class,
                self::RELATION => Relation\HasOne::class,
            ],
            Relation::BELONGS_TO => [
                self::LOADER => Select\Loader\BelongsToLoader::class,
                self::RELATION => Relation\BelongsTo::class,
            ],
            Relation::REFERS_TO => [
                self::LOADER => Select\Loader\BelongsToLoader::class,
                self::RELATION => Relation\RefersTo::class,
            ],
            Relation::HAS_MANY => [
                self::LOADER => Select\Loader\HasManyLoader::class,
                self::RELATION => Relation\HasMany::class,
            ],
            Relation::MANY_TO_MANY => [
                self::LOADER => Select\Loader\ManyToManyLoader::class,
                self::RELATION => Relation\ManyToMany::class,
            ],
            Relation::MORPHED_HAS_ONE => [
                self::LOADER => Select\Loader\Morphed\MorphedHasOneLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasOne::class,
            ],
            Relation::MORPHED_HAS_MANY => [
                self::LOADER => Select\Loader\Morphed\MorphedHasManyLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasMany::class,
            ],
            Relation::BELONGS_TO_MORPHED => [
                self::RELATION => Relation\Morphed\BelongsToMorphed::class,
            ],
        ]);
    }
}
