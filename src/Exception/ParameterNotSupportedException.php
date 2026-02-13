<?php

namespace OpenSolid\CallableInvoker\Exception;

class ParameterNotSupportedException extends \InvalidArgumentException
{
    public function __construct(string $parameterName, string $identifier)
    {
        parent::__construct(\sprintf('Could not resolve value for parameter "$%s" in "%s".', $parameterName, $identifier));
    }
}
