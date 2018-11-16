<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

class RelationContext implements RelationContextInterface
{
    /** @var \SplObjectStorage */
    private $context;

    /**
     * @param \SplObjectStorage $context
     */
    public function __construct(\SplObjectStorage $context = null)
    {
        $this->context = $context ?? new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function has($entity): bool
    {
        return $this->context->offsetExists($entity);
    }

    /**
     * @inheritdoc
     */
    public function get($entity)
    {
        try {
            return $this->context->offsetGet($entity);
        } catch (\UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function set($entity, $context)
    {
        $this->context->offsetSet($entity, $context);
    }
}