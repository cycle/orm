<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation\Traits;

use Doctrine\Common\Collections\Collection;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Promise\Collection\CollectionPromiseInterface;
use Spiral\Cycle\Relation\Pivoted;

trait PivotedTrait
{
    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof CollectionPromiseInterface && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Pivoted\PivotedCollectionInterface) {
            return new Pivoted\PivotedStorage($data->toArray(), $data->getPivotContext());
        }

        if ($data instanceof Collection) {
            return new Pivoted\PivotedStorage($data->toArray());
        }

        return new Pivoted\PivotedStorage();
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [new Pivoted\PivotedCollection(), null];
        }

        // will take care of all the loading and scoping
        $p = new Pivoted\PivotedPromise($this->orm, $this->target, $this->schema, $innerKey);
        $p->setScope($this->getConstrain());

        return [new Pivoted\PivotedCollectionPromisePromise($p), $p];
    }
}