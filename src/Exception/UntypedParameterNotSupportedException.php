<?php

namespace OpenSolid\CallableInvoker\Exception;

class UntypedParameterNotSupportedException extends ParameterNotSupportedException
{
    public static function create(\ReflectionParameter $parameter): self
    {
        return new self(\sprintf('Untyped parameter "$%s" is not supported in "%s".', $parameter->getName(), self::identifierOf($parameter)));
    }
}
