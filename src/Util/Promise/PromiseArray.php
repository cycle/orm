<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Promise;


use Spiral\ORM\PromiseInterface;
use Spiral\ORM\RepositoryInterface;

class PromiseArray implements PromiseInterface
{
    /** @var RepositoryInterface|null */
    private $repository;

    /** @var array */
    private $scope;

    /** @var array */
    private $orderBy;

    /** @var mixed */
    private $result;

    /**
     * PromiseMany constructor.
     *
     * @param RepositoryInterface $repository
     * @param array               $scope
     * @param array               $orderBy
     */
    public function __construct(RepositoryInterface $repository, array $scope, array $orderBy = [])
    {
        $this->repository = $repository;
        $this->scope = $scope;
        $this->orderBy = $orderBy;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->promise);
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return $this->scope;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->repository)) {
            $this->result = $this->repository->findAll($this->filter($this->scope), $this->filter($this->orderBy));
            $this->repository = null;
        }

        return $this->result;
    }

    // todo: move to selector
    private function filter(array $in): array
    {
        $out = [];
        foreach ($in as $key => $value) {
            $out[str_replace('@.', '', $key)] = $value;
        }

        return $out;
    }
}