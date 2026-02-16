<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\FunctionNotSupportedException;

interface CallableDecoratorInterface
{
    public function supports(CallableMetadata $metadata): bool;

    /**
     * @throws FunctionNotSupportedException if the function cannot be decorated
     */
    public function decorate(\Closure $closure, CallableMetadata $metadata): \Closure;
}
