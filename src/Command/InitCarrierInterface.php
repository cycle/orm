<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Command;

use Spiral\Cycle\Command\Branch\Split;

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