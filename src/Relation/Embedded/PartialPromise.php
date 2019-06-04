<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Relation\Embedded;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;

/**
 * Provides ability to resolve partial promise to the embedded object.
 */
final class PartialPromise implements PromiseInterface
{
    /** @var ORMInterface|null @internal */
    private $orm;

    /** @var string|null */
    private $target;

    /** @var array */
    private $scope;

    /** @var mixed */
    private $resolved;

    /** @var array */
    private $data = [];

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $scope
     */
    public function __construct(ORMInterface $orm, string $target, array $scope)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->scope = $scope;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return $this->orm === null;
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->target;
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
        if (is_null($this->orm)) {
            return $this->resolved;
        }

        // fallback to low level query
        $select = new Select($this->orm, $this->target);
        $this->data = $select->where($this->scope)->buildQuery()->fetchAll();

        if (count($this->data) === 1) {
            $this->data = $this->mapData($this->data[0]);

            // found the partial selection
            list($this->resolved, $this->data) = $this->orm->getMapper($this->target)->init($this->data);
            $this->resolved = $this->orm->getMapper($this->target)->hydrate($this->resolved, $this->data);
        }

        $this->orm = null;

        return $this->resolved;
    }

    /**
     * @return array
     */
    public function __origData(): array
    {
        $this->__resolve();
        return $this->data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function mapData(array $data): array
    {
        $columns = $this->orm->getSchema()->define($this->target, Schema::COLUMNS);
        if (!is_int(key($columns))) {
            $columns = array_keys($columns);
        }

        return array_combine($columns, array_values($data));
    }
}