<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;


use Spiral\ORM\Mapper\ProxyFactoryInterface;
use Spiral\ORM\Node;
use Spiral\ORM\Util\Promise\PromiseOne;

trait PromiseMorphedTrait
{
    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        $target = $this->fetchKey($parentNode, $this->morphKey);
        $scope = [$this->outerKey => $innerKey];

        if (!empty($e = $this->orm->get($target, $scope, false))) {
            return [$e, $e];
        }

        $mapper = $this->getMapper($target);
        if ($mapper instanceof ProxyFactoryInterface) {
            $p = $mapper->initProxy($scope);
        } else {
            $p = new PromiseOne($this->orm, $target, $scope);
        }

        return [$p, $p];
    }
}