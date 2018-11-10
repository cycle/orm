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
}