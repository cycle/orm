<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

use Spiral\ORM\Relation\Pivoted\PivotedCollectionInterface;

class Post implements ImagedInterface
{
    public $title;
    public $content;
    public $image;

    /** @var Comment[]|PivotedCollectionInterface */
    public $comments;
}