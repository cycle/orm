<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * Node with ability to push it's data into referenced tree location.
 *
 * @internal
 */
final class SingularNode extends AbstractNode
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @param string[] $innerKeys Inner relation keys (for example user_id)
     * @param string[]|null $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(
        array $columns,
        array $primaryKeys,
        protected array $innerKeys,
        ?array $outerKeys
    ) {
        parent::__construct($columns, $outerKeys);
        $this->setDuplicateCriteria($primaryKeys);
    }

    protected function push(array &$data): void
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to register data tree, parent is missing.');
        }

        foreach ($this->innerKeys as $key) {
            if ($data[$key] === null) {
                //No data was loaded
                return;
            }
        }

        $this->parent->mount(
            $this->container,
            $this->indexName,
            $this->intersectData($this->innerKeys, $data),
            $data
        );
    }
}
