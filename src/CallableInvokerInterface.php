<?php

namespace OpenSolid\CallableInvoker;

interface CallableInvokerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function invoke(callable $callable, array $context = []): mixed;
}
