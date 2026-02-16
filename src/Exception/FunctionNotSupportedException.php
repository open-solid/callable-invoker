<?php

namespace OpenSolid\CallableInvoker\Exception;

class FunctionNotSupportedException extends \InvalidArgumentException
{
    public static function create(\ReflectionFunction $function): self
    {
        $scope = $function->getClosureScopeClass()?->getName();
        $identifier = null !== $scope ? $scope.'::'.$function->getName() : $function->getName();

        return new self(\sprintf('Function "%s" is not supported.', $identifier));
    }
}
