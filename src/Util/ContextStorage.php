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
    private $data;

    /** @var \SplObjectStorage */
    private $context;

    /**
     * @param array             $data
     * @param \SplObjectStorage $context
     */
    public function __construct(array $data, \SplObjectStorage $context)
    {
        $this->data = $data;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getContext(): \SplObjectStorage
    {
        return $this->context;
    }
}