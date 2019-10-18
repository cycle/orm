<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\ORM\Command\Branch\Split;

/**
 * Represents commands required to init object presence in persistence storage.
 *
 * [split] -> [init] / [update]
 *
 * @see Split
 */
interface InitCarrierInterface extends ContextCarrierInterface
{
}
