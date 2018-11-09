<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Entities;

use Psr\SimpleCache\CacheInterface;
use Spiral\Core\Component;
use Spiral\Database\Builders\SelectQuery;
use Spiral\ORM\Entities\Loaders\RootLoader;
use Spiral\ORM\Entities\Nodes\OutputNode;
use Spiral\ORM\Exceptions\SelectorException;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\RecordInterface;
use Spiral\Pagination\PaginatorAwareInterface;
use Spiral\Pagination\PaginatorInterface;

/**
 * Attention, RecordSelector DOES NOT extends QueryBuilder but mocks it!
 *
 * @method $this where(...$args);
 * @method $this andWhere(...$args);
 * @method $this orWhere(...$args);
 *
 * @method $this having(...$args);
 * @method $this andHaving(...$args);
 * @method $this orHaving(...$args);
 *
 * @method $this paginate($limit = 25, $page = 'page')
 *
 * @method $this orderBy($expression, $direction = 'ASC');
 *
 * @method $this distinct()
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
class RecordSelector2222 implements \IteratorAggregate, \Countable, PaginatorAwareInterface
{

    /**
     * Get RecordIterator (entity iterator) for a requested data. Provide cache key and lifetime in
     * order to cache request data.
     *
     * @param string              $cacheKey
     * @param int|\DateInterval   $ttl
     * @param CacheInterface|null $cache Can be automatically resoled via ORM container scope.
     *
     * @return RecordIterator|RecordInterface[]
     */
    public function getIterator(
        string $cacheKey = '',
        $ttl = 0,
        CacheInterface $cache = null
    ): RecordIterator {
        if (!empty($cacheKey)) {
            /**
             * When no cache is provided saturate it using container scope
             *
             * @var CacheInterface $cache
             */
            $cache = $this->saturate($cache, CacheInterface::class);

            if ($cache->has($cacheKey)) {
                $data = $cache->get($cacheKey);
            } else {
                //Cache parsed tree with all sub queries executed!
                $cache->set($cacheKey, $data = $this->fetchData(), $ttl);
            }
        } else {
            $data = $this->fetchData();
        }

        return new RecordIterator($data, $this->class, $this->orm);
    }


    /**
     * Attention, column will be quoted by driver!
     *
     * @param string|null $column When column is null DISTINCT(PK) will be generated.
     *
     * @return int
     */
    public function count(string $column = null): int
    {
        if (is_null($column)) {
            if (!empty($this->loader->primaryKey())) {
                //@tuneyourserver solves the issue with counting on queries with joins.
                $column = "DISTINCT({$this->loader->primaryKey()})";
            } else {
                $column = '*';
            }
        }

        return $this->compiledQuery()->count($column);
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaginator(): bool
    {
        return $this->loader->initialQuery()->hasPaginator();
    }

    /**
     * {@inheritdoc}
     */
    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->loader->initialQuery()->setPaginator($paginator);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginator(bool $prepare = true): PaginatorInterface
    {
        return $this->loader->compiledQuery()->getPaginator($prepare);
    }

    /**
     * Bypassing call to primary select query.
     *
     * @param string $name
     * @param        $arguments
     *
     * @return $this|mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array(strtoupper($name), ['AVG', 'MIN', 'MAX', 'SUM'])) {
            //One of aggregation requests
            $result = call_user_func_array([$this->compiledQuery(), $name], $arguments);
        } else {
            //Where condition or statement
            $result = call_user_func_array([$this->loader->initialQuery(), $name], $arguments);
        }

        if ($result === $this->loader->initialQuery()) {
            return $this;
        }

        return $result;
    }
}
