<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Relation\Traits;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseOne;
use Cycle\ORM\Promise\ProxyFactoryInterface;

trait PromiseOneTrait
{
    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        if (!empty($e = $this->orm->getHeap()->find($this->target, $this->outerKey, $innerKey))) {
            return [$e, $e];
        }

        $scope = [$this->outerKey => $innerKey];

        $m = $this->getMapper();
        if ($m instanceof ProxyFactoryInterface) {
            $p = $m->makeProxy($scope);
        } else {
            $p = new PromiseOne($this->orm, $this->target, $scope);
            $p->setConstrain($this->getConstrain());
        }

        return [$p, $p];
    }
}