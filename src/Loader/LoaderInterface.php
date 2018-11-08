<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Loader;

use Spiral\Treap\Exception\LoaderException;
use Spiral\Treap\Node\AbstractNode;

/**
 * Loaders provide the ability to create data tree based on set of nested queries or parse resulted
 * rows to properly link child data into valid place.
 */
interface LoaderInterface
{
    /**
     * Loader specific entity class.
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Declare loader context, parent will declare TreeParser where loader can mount his data, in
     * addition each loader will declare set of fields to be aggregated in a parent and used to
     * properly load connected data (AbstractLoaders can also be loaded directly thought joining
     * into SQL query).
     *
     * Attention, make sure that loader accepts given parent.
     *
     * @param LoaderInterface $parent
     * @param array           $options
     *
     * @return LoaderInterface
     * @throws LoaderException
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface;

    /**
     * Create node to represent collected data in a tree form. Nodes can declare dependencies
     * to parent and automatically put collected data in a proper place.
     *
     * @return AbstractNode
     */
    public function createNode(): AbstractNode;

    /**
     * Load data into previously created node.
     *
     * @param AbstractNode $node
     *
     * @throws LoaderException
     */
    public function loadData(AbstractNode $node);
}