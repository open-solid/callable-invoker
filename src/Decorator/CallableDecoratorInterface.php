<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\CallableNotSupportedException;

interface CallableDecoratorInterface
{
    public function supports(CallableMetadata $metadata): bool;

    /**
     * @throws CallableNotSupportedException if the callable cannot be decorated
     */
    public function decorate(ClosureInvoker $invoker, CallableMetadata $metadata): mixed;
}
