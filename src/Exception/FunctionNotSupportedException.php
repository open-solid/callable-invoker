<?php

namespace OpenSolid\CallableInvoker\Exception;

class FunctionNotSupportedException extends \InvalidArgumentException
{
    public function __construct(string $identifier)
    {
        parent::__construct(\sprintf('Function "%s" is not supported.', $identifier));
    }
}
