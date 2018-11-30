<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\ORM\Exception\ConfigException;
use Spiral\ORM\Loader;
use Spiral\ORM\Relation;

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

    public function getSchema($type): Autowire
    {
        if (!isset($this->config[$type][self::SCHEMA])) {
            throw new ConfigException("Unable to get relation schema `{$type}`.");
        }

        return new Autowire($this->config[$type][self::SCHEMA]);
    }

    public static function createDefault()
    {
        return new static([
            Relation::HAS_ONE              => [
                self::LOADER   => Loader\Relation\HasOneLoader::class,
                self::RELATION => Relation\HasOneRelation::class
            ],
            Relation::BELONGS_TO           => [
                self::LOADER   => Loader\Relation\BelongsToLoader::class,
                self::RELATION => Relation\BelongsToRelation::class
            ],
            Relation::REFERS_TO            => [
                self::LOADER   => Loader\Relation\BelongsToLoader::class,
                self::RELATION => Relation\RefersToRelation::class
            ],
            Relation::HAS_MANY             => [
                self::LOADER   => Loader\Relation\HasManyLoader::class,
                self::RELATION => Relation\HasManyRelation::class
            ],
            Relation::MANY_TO_MANY         => [
                self::LOADER   => Loader\Relation\ManyToManyLoader::class,
                self::RELATION => Relation\ManyToManyRelation::class
            ],
            Relation::MANY_TO_MANY_PIVOTED => [
                self::LOADER   => Loader\Relation\ManyToManyLoader::class,
                self::RELATION => Relation\ManyToMany\PivotedRelation::class
            ],
            Relation::MORPHED_HAS_ONE      => [
                self::LOADER   => Loader\Relation\Morphed\MorphedHasOneLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasOneRelation::class
            ],
            Relation::MORPHED_HAS_MANY     => [
                self::LOADER   => Loader\Relation\Morphed\MorphedHasManyLoader::class,
                self::RELATION => Relation\Morphed\MorphedHasManyRelation::class
            ],
            Relation::BELONGS_TO_MORPHED   => [
                self::RELATION => Relation\Morphed\BelongsToMorphedRelation::class
            ]
        ]);
    }
}