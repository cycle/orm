<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

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

        $scope = [
            $this->outerKey => $innerKey
        ];

        if (!empty($e = $this->orm->get($this->target, $scope, false))) {
            return [$e, $e];
        }

        $p = new PromiseOne($this->orm, $this->target, $scope);

        $m = $this->getSource();
        if ($m instanceof ProxyFactoryInterface) {
            $p = $m->makeProxy($p);
        }

        return [$p, $p];
    }
}