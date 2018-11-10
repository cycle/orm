<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\UserDefined;

use Spiral\ORM\AbstractMapper;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\RelationMap;
use Zend\Hydrator\Reflection;

class TestMapper extends AbstractMapper
{
    private $hydrator;

    public function __construct(ORMInterface $orm)
    {
        parent::__construct($orm);
        $this->hydrator = new Reflection();
    }

    public function make(array $data, RelationMap $relmap = null)
    {
        return $this->hydrator->hydrate($data, new TestEntity());
    }

    protected function getFields($entity): array
    {
        return $this->hydrator->extract($entity);
    }

    // todo: in the heap?
    protected function setField($entity, $field, $value)
    {
        $this->hydrator->hydrate([$field => $value], $entity);
    }

    // todo: from the heap?
    protected function getField($entity, $field)
    {
        return $this->hydrator->extractName($field, $entity);
    }
}