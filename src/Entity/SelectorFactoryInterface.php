<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Entity;

use Spiral\ORM\MapperInterface;

interface SelectorFactoryInterface extends MapperInterface
{
    public function getSelector();
}