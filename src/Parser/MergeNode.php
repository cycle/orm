<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * @internal
 */
final class MergeNode extends AbstractNode
{
    /** @var string[] */
    protected array $innerKeys;

    /**
     * @param array      $columns
     * @param array      $primaryKeys
     * @param array      $innerKeys Inner relation keys (for example user_id)
     * @param array|null $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(array $columns, array $primaryKeys, array $innerKeys, ?array $outerKeys)
    {
        parent::__construct($columns, $outerKeys);
        $this->setDuplicateCriteria($primaryKeys);

        $this->innerKeys = $innerKeys;
    }

    protected function push(array &$data): void
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to register data tree, parent is missing.');
        }

        $parent = $this->parent;
        do {
            $parent->mergeData(
                $this->indexName,
                $this->intersectData($this->innerKeys, $data),
                $data,
                false
            );
            $parent = $parent->parent;
        } while ($parent instanceof self);
    }
}
