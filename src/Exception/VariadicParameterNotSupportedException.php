<?php

namespace OpenSolid\CallableInvoker\Exception;

class VariadicParameterNotSupportedException extends ParameterNotSupportedException
{
    public static function create(\ReflectionParameter $parameter): self
    {
        return new self(\sprintf('Variadic parameter "$%s" is not supported in "%s".', $parameter->getName(), self::identifierOf($parameter)));
    }
}
