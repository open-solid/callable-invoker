<?php

namespace OpenSolid\CallableInvoker;

interface CallableParameterValueResolverInterface
{
    /**
     * Resolves the values for each parameter of the given callable using registered value resolvers.
     *
     * @param array<string, mixed> $context
     * @param list<string>         $groups
     *
     * @return list<mixed>
     */
    public function resolve(callable $callable, array $context = [], array $groups = [CallableInvokerInterface::DEFAULT_GROUP]): array;
}
