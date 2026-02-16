<?php

namespace OpenSolid\CallableInvoker\Exception;

class ParameterNotSupportedException extends \InvalidArgumentException
{
    public static function create(\ReflectionParameter $parameter): self
    {
        return new self(\sprintf('Could not resolve value for parameter "$%s" in "%s".', $parameter->getName(), static::identifierOf($parameter)));
    }

    protected static function identifierOf(\ReflectionParameter $parameter): string
    {
        $function = $parameter->getDeclaringFunction();
        $scope = $function instanceof \ReflectionFunction ? $function->getClosureScopeClass()?->getName() : null;

        return null !== $scope ? $scope.'::'.$function->getName() : $function->getName();
    }
}
