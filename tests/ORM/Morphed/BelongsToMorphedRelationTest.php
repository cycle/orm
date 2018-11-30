<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Morphed;

use Spiral\ORM\Tests\BaseTest;

// Belongs to morphed relation does not support eager loader, this relation can only work using lazy loading
// and promises.
abstract class BelongsToMorphedRelationTest extends BaseTest
{

}