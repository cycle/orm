<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Loader\Traits;


trait ChainTrait
{
    /**
     * Check if given relation is actually chain of relations.
     *
     * @param string $relation
     *
     * @return bool
     */
    private function isChain(string $relation): bool
    {
        return strpos($relation, '.') !== false;
    }

    /**
     * @see loadRelation()
     * @see joinRelation()
     *
     * @param string $chain
     * @param array  $options Final loader options.
     * @param bool   $join    See loadRelation().
     *
     * @return LoaderInterface
     *
     * @throws LoaderException When one of chain elements is not actually chainable (let's say ODM
     *                         loader).
     */
    private function loadChain(string $chain, array $options, bool $join): LoaderInterface
    {
        $position = strpos($chain, '.');

        //Chain of relations provided (relation.nestedRelation)
        $child = $this->loadRelation(
            substr($chain, 0, $position),
            [],
            $join
        );

        if (!$child instanceof AbstractLoader) {
            throw new LoaderException(sprintf(
                "Loader '%s' does not support chain relation loading",
                get_class($child)
            ));
        }

        //Loading nested relation thought chain (chainOptions prior to user options)
        return $child->loadRelation(
            substr($chain, $position + 1),
            $options,
            $join
        );
    }
}