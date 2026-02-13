<?php

namespace OpenSolid\CallableInvoker\Exception;

class VariadicParameterNotSupportedException extends ParameterNotSupportedException
{
    public function __construct(string $parameterName, string $identifier)
    {
        \InvalidArgumentException::__construct(\sprintf('Variadic parameter "$%s" is not supported in "%s".', $parameterName, $identifier));
    }
}
