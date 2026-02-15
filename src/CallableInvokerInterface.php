<?php

namespace OpenSolid\CallableInvoker;

interface CallableInvokerInterface
{
    public const string DEFAULT_GROUP = '__NONE__';

    /**
     * @param array<string, mixed> $context
     */
    public function invoke(callable $callable, array $context = [], string $group = self::DEFAULT_GROUP): mixed;
}
