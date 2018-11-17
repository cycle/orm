<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

class ContextStorage
{
    /** @var array */
    private $elements;

    /** @var \SplObjectStorage */
    private $context;

    /**
     * @param array             $elements
     * @param \SplObjectStorage $context
     */
    public function __construct(array $elements, \SplObjectStorage $context)
    {
        $this->elements = $elements;
        $this->context = $context;
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
}