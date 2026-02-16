<?php

namespace OpenSolid\CallableInvoker\Exception;

class CallableNotSupportedException extends \InvalidArgumentException
{
    public static function create(\ReflectionFunction $function): self
    {
        $scope = $function->getClosureScopeClass()?->getName();
        $identifier = null !== $scope ? $scope.'::'.$function->getName() : $function->getName();

        return new self(\sprintf('Callable "%s" is not supported.', $identifier));
    }
}
