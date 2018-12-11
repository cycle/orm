<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;

use Spiral\Cycle\Relation\Pivoted\PivotedCollectionInterface;

class Post implements ImagedInterface
{
    public $title;
    public $content;
    public $image;

    /** @var Comment[]|PivotedCollectionInterface */
    public $comments;
}