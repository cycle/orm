<?php

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
    public const ROLE_KEY = '@role';

    /**
     * Return the relation alias.
     */
    public function getAlias(): string;

    /**
     * Loader specific entity class.
     */
    public function getTarget(): string;

    /**
     * Get column name related to internal key.
     */
    public function fieldAlias(string $field): ?string;

    /**
     * Initiate loader with it's position and options in dependency tree.
     *
     * @throws LoaderException
     */
    public function withContext(self $parent, array $options = []): self;

    /**
     * Create node to represent collected data in a tree form. Nodes can declare dependencies
     * to parent and automatically put collected data in a proper place.
     */
    public function createNode(): AbstractNode;

    /**
     * Load data into previously created node.
     *
     * @param bool $includeRole Turn on to include {@see LoaderInterface::ROLE_KEY} key in the result data
     *
     * @throws LoaderException
     */
    public function loadData(AbstractNode $node, bool $includeRole = false): void;

    public function setSubclassesLoading(bool $enabled): void;

    /**
     * @return bool True if this loader loads parents or children for JTI
     */
    public function isHierarchical(): bool;
}
