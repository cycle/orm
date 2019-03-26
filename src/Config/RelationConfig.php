<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Config;

use Cycle\ORM\Exception\ConfigException;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;

class RelationConfig extends InjectableConfig
{
    public const LOADER   = 'loader';
    public const RELATION = 'relation';
    public const SCHEMA   = 'schema';

    protected $config = [];

    public function getLoader($type): Autowire
    {
        if (!isset($this->config[$type][self::LOADER])) {
            throw new ConfigException("Unable to get relation loader `{$type}`.");
        }

        return new Autowire($this->config[$type][self::LOADER]);
    }

    public function getRelation($type): Autowire
    {
        if (!isset($this->config[$type][self::RELATION])) {
            throw new ConfigException("Unable to get relation `{$type}`.");
        }

        return new Autowire($this->config[$type][self::RELATION]);
    }

    public static function getDefault()
    {
        return new static([
            Relation::HAS_ONE            => [
                self::LOADER   => Select\Loader\HasOneLoader::class,
                self::RELATION => Relation\HasOne::class
            ],
            Relation::BELONGS_TO         => [
                self::LOADER   => Select\Loader\BelongsToLoader::class,
                self::RELATION => Relation\BelongsTo::class
            ],
            Relation::REFERS_TO          => [
                self::LOADER   => Select\Loader\BelongsToLoader::class,
                self::RELATION => Relation\RefersTo::class
            ],
            Relation::HAS_MANY           => [
                self::LOADER   => Select\Loader\HasManyLoader::class,
                self::RELATION => Relation\HasMany::class
            ],
            Relation::MANY_TO_MANY       => [
                self::LOADER   => Select\Loader\ManyToManyLoader::class,
                self::RELATION => Relation\ManyToMany::class
            ],
            Relation::MORPHED_HAS_ONE    => [
                self::LOADER   => Select\Loader\Morphed\MorphedHasOneLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasOne::class
            ],
            Relation::MORPHED_HAS_MANY   => [
                self::LOADER   => Select\Loader\Morphed\MorphedHasManyLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasMany::class
            ],
            Relation::BELONGS_TO_MORPHED => [
                self::RELATION => Relation\Morphed\BelongsToMorphed::class
            ]
        ]);
    }
}