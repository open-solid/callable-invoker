<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

interface FunctionDecoratorGroupsInterface
{
    /**
     * @return iterable<FunctionDecoratorInterface>
     */
    public function get(string $group): iterable;

    public function has(string $group): bool;
}
