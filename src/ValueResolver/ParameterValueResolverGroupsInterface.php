<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver;

interface ParameterValueResolverGroupsInterface
{
    /**
     * @return iterable<ParameterValueResolverInterface>
     */
    public function get(string $group): iterable;

    public function has(string $group): bool;
}
