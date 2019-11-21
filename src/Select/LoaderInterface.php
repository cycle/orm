<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Parser\AbstractNode;

/**
 * Loaders provide the ability to create data tree based on set of nested queries or parse resulted
 * rows to properly link child data into valid place.
 */
interface LoaderInterface
{
    /**
     * Return the relation alias.
     *
     * @return string
     */
    public function getAlias(): string;

    /**
     * Loader specific entity class.
     *
     * @return string
     */
    public function getTarget(): string;

    /**
     * Get column name related to internal key.
     *
     * @param string $key
     * @return string
     */
    public function fieldAlias(string $key): string;

    /**
     * Initiate loader with it's position and options in dependency tree.
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
