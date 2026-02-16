<?php

namespace OpenSolid\CallableInvoker;

interface CallableInvokerInterface
{
    public const string DEFAULT_GROUP = '__NONE__';

    /**
     * @param array<string, mixed> $context
     * @param list<string>         $groups
     */
    public function invoke(callable $callable, array $context = [], array $groups = [self::DEFAULT_GROUP]): mixed;
}
