<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM2\Entities;

use Psr\SimpleCache\CacheInterface;
use Spiral\Core\Component;
use Spiral\Database\Builders\SelectQuery;
use Spiral\ORM\Entities\Loaders\RootLoader;
use Spiral\ORM\Entities\Nodes\OutputNode;
use Spiral\ORM\Exceptions\SelectorException;
use Spiral\ORM\RecordInterface;
use Spiral\Pagination\PaginatorAwareInterface;
use Spiral\Pagination\PaginatorInterface;

/**
 * Attention, RecordSelector DOES NOT extends QueryBuilder but mocks it!
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
}
