<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\Exception\FunctionNotSupportedException;
use OpenSolid\CallableInvoker\FunctionMetadata;

interface FunctionDecoratorInterface
{
    public function supports(FunctionMetadata $metadata): bool;

    /**
     * @throws FunctionNotSupportedException if the function cannot be decorated
     */
    public function decorate(\Closure $function, FunctionMetadata $metadata): \Closure;
}
