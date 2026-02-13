<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\Exception\FunctionNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

interface FunctionDecoratorInterface
{
    public function supports(Metadata $metadata): bool;

    /**
     * @throws FunctionNotSupportedException if the function cannot be decorated
     */
    public function decorate(\Closure $function, Metadata $metadata): \Closure;
}
