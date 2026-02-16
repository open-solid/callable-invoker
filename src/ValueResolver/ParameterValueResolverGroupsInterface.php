<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver;

interface ParameterValueResolverGroupsInterface
{
    /**
     * @param list<string> $groups
     *
     * @return iterable<ParameterValueResolverInterface>
     */
    public function get(array $groups): iterable;
}
