<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * Node with ability to push it's data into referenced tree location.
 *
 * The node pushes role name along with data to help identifying the relation.
 *
 * @internal
 */
final class SingularRoleNode extends AbstractNode
{
    /**
     * @param non-empty-string[] $columns
     * @param non-empty-string[] $primaryKeys
     * @param non-empty-string[] $innerKeys Inner relation keys (for example user_id)
     * @param non-empty-string[] $outerKeys Outer (parent) relation keys (for example id = parent.id)
     */
    public function __construct(
        array $columns,
        array $primaryKeys,
        protected array $innerKeys,
        array $outerKeys,
        protected string $role,
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

        // The role may be predefined in data (for example, in STI or JTI scenarios)
        $role = $data['@role'] ?? $this->role;
        $data['@role'] = $role;

        $this->parent->mount(
            $this->container,
            $this->indexName,
            ['@role' => $role, ...$this->intersectData($this->innerKeys, $data)],
            $data,
        );
    }
}
