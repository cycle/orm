<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\ORM\Command\AbstractCommand;
use Spiral\ORM\Command\FloatingCommandInterface;

// wait until link is established
class LinkCommand extends AbstractCommand implements FloatingCommandInterface
{
    public function isDelayed(): bool
    {

    }
}