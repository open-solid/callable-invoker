<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

interface FunctionDecoratorGroupsInterface
{
    /**
     * @param list<string> $groups
     *
     * @return iterable<FunctionDecoratorInterface>
     */
    public function get(array $groups): iterable;
}
