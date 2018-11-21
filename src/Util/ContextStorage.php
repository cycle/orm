<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

/**
 * Carry information about ordered list of entities and associated pivot context.
 */
final class ContextStorage
{
    /** @var array */
    private $elements;

    /** @var \SplObjectStorage */
    private $context;

    /**
     * @param array             $elements
     * @param \SplObjectStorage $context
     */
    public function __construct(array $elements = [], \SplObjectStorage $context = null)
    {
        $this->elements = $elements;
        $this->context = $context ?? new \SplObjectStorage();
    }

    /**
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getContext(): \SplObjectStorage
    {
        return $this->context;
    }

    /**
     * Check if entity belongs to the storage.
     *
     * @param object $entity
     * @return bool
     */
    public function has($entity)
    {
        return in_array($entity, $this->elements, true);
    }

    /**
     * Get entity context.
     *
     * @param object $entity
     * @return mixed|null
     */
    public function get($entity)
    {
        try {
            return $this->context->offsetGet($entity);
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }
}