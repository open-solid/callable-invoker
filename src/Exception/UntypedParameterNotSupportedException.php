<?php

namespace OpenSolid\CallableInvoker\Exception;

class UntypedParameterNotSupportedException extends ParameterNotSupportedException
{
    public function __construct(string $parameterName, string $identifier)
    {
        \InvalidArgumentException::__construct(\sprintf('Untyped parameter "$%s" is not supported in "%s".', $parameterName, $identifier));
    }
}
