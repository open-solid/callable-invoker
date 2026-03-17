<?php

namespace OpenSolid\CallableInvoker;

interface CallableDecoratorProviderInterface
{
    /**
     * Wraps the given callable with registered decorators, returning a decorated closure.
     *
     * @param array<string, mixed> $context
     * @param list<string>         $groups
     */
    public function decorate(callable $callable, array $context = [], array $groups = [CallableInvokerInterface::DEFAULT_GROUP]): \Closure;
}
