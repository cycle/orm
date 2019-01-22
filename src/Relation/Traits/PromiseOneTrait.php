<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation\Traits;

use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\ProxyFactoryInterface;
use Spiral\Cycle\Promise\PromiseOne;

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

        $scope = [$this->outerKey => $innerKey];
        if (!empty($e = $this->orm->getHeap()->find($this->target, $scope))) {
            return [$e, $e];
        }

        $p = new PromiseOne($this->orm, $this->target, $scope);
        $p->setConstrain($this->getConstrain());

        $m = $this->getMapper();
        if ($m instanceof ProxyFactoryInterface) {
            $p = $m->makeProxy($p);
        }

        return [$p, $p];
    }
}